WooCommerce / WordPress
=======================

For WooCommerce 8+ on PHP 8.4+, as a plugin. Of all the hosts here, WordPress is the hardest fit
for the SDK. It has no container and no autoloader of its own, and it does very little type
checking. Most of this page is about keeping your plugin code and WordPress separate.

Autoloading, and the dependency problem
---------------------------------------

WordPress loads all active plugins into one process that shares one global class table. If two
plugins ship different versions of `symfony/serializer`, the site hits a fatal error, and you can't
control what else the merchant has installed.

**Prefix your dependencies.** At build time, use [PHP-Scoper](https://github.com/humbug/php-scoper)
or [Mozart](https://github.com/coenjacobs/mozart) to move the SDK and its dependencies into your own
namespace:

```json
{
    "name": "your-vendor/woo-polkurier",
    "require": {
        "php": "~8.4.0||~8.5.0",
        "polkurier/polkurier-sdk": "^2.0"
    },
    "require-dev": {
        "humbug/php-scoper": "^0.18"
    },
    "scripts": {
        "build": [
            "composer install --no-dev --optimize-autoloader",
            "php-scoper add-prefix --output-dir=build/vendor --force",
            "composer dump-autoload --working-dir=build --classmap-authoritative"
        ]
    }
}
```

`scoper.inc.php`:

```php
<?php

return [
    'prefix' => 'YourVendor\\WooPolkurier\\Vendor',
    'finders' => [
        Isolated\Symfony\Component\Finder\Finder::create()
            ->files()
            ->in('vendor')
            ->name(['*.php', 'LICENSE', 'composer.json']),
    ],
    // WordPress and WooCommerce symbols must not be prefixed.
    'exclude-namespaces' => ['Automattic', 'WC'],
    'exclude-functions'  => ['add_action', 'add_filter', 'get_option', 'wp_die'],
];
```

You must do this for any plugin you distribute. Without it, the plugin works on your machine and
breaks on any site that also runs a plugin with an older Symfony.

Plugin bootstrap
----------------

```php
<?php
/**
 * Plugin Name: Polkurier for WooCommerce
 * Requires PHP: 8.4
 * Requires Plugins: woocommerce
 */

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        return;
    }

    Plugin::instance()->boot();
}, 20);
```

The `plugins_loaded` priority is 20 because WooCommerce loads at 10, and the settings API below
needs WooCommerce to be loaded.

Settings
--------

`WC_Integration` adds a settings tab under WooCommerce > Settings > Integration. WooCommerce
renders and saves the fields for you:

```php
<?php

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

use WC_Integration;

final class Settings extends WC_Integration
{
    public function __construct()
    {
        $this->id = 'polkurier';
        $this->method_title = __('Polkurier', 'woo-polkurier');
        $this->method_description = __('Ship orders through Polkurier.pl.', 'woo-polkurier');

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'verify_credentials']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'login' => [
                'title'       => __('Customer ID', 'woo-polkurier'),
                'type'        => 'text',
                'description' => __('The numeric id, not an email address.', 'woo-polkurier'),
            ],
            'token' => [
                'title' => __('API token', 'woo-polkurier'),
                'type'  => 'password',
            ],
            'sandbox' => [
                'title'   => __('Sandbox', 'woo-polkurier'),
                'type'    => 'checkbox',
                'default' => 'yes',
            ],
        ];
    }

    public function verify_credentials(): void
    {
        try {
            Client::get()->utils()->testAuthApi(new AuthorizationMessage(
                token: (string)$this->get_option('token'),
                login: (string)$this->get_option('login'),
            ));

            \WC_Admin_Settings::add_message(__('Credentials accepted.', 'woo-polkurier'));
        } catch (\Throwable $e) {
            \WC_Admin_Settings::add_error(
                sprintf(__('Polkurier rejected the credentials: %s', 'woo-polkurier'), $e->getMessage()),
            );
        }
    }
}
```

Register the integration:

```php
add_filter('woocommerce_integrations', static function (array $integrations): array {
    $integrations[] = Settings::class;

    return $integrations;
});
```

> WordPress stores options in plain text. Secrets usually go in `wp-config.php`, so support a
> constant that overrides the setting. That way a site can keep the token out of the database:
>
> ```php
> $token = defined('POLKURIER_TOKEN') ? POLKURIER_TOKEN : $settings['token'];
> ```

Building the client
-------------------

WordPress has no container, so use a lazy singleton:

```php
<?php

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Config;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Http\Request\AuthorizationMessage;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\PolkurierWebservice;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Serializer\SerializerConfig;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Serializer\SerializerProvider;
use YourVendor\WooPolkurier\Vendor\Symfony\Component\Cache\Adapter\PhpFilesAdapter;

final class Client
{
    private static ?PolkurierWebservice $client = null;

    public static function get(): PolkurierWebservice
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $settings = get_option('woocommerce_polkurier_settings', []);
        $upload   = wp_upload_dir();

        return self::$client = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(
                    token: defined('POLKURIER_TOKEN') ? POLKURIER_TOKEN : (string)($settings['token'] ?? ''),
                    login: (string)($settings['login'] ?? ''),
                ),
                platform: 'woocommerce',
                platformVersion: defined('WC_VERSION') ? WC_VERSION : '0',
                sandbox: ($settings['sandbox'] ?? 'yes') === 'yes',
            ),
            logger: new WooLogger(),
            serializerProvider: new SerializerProvider(new SerializerConfig(
                metadataCache: new PhpFilesAdapter('polkurier', 0, $upload['basedir'] . '/polkurier-cache'),
            )),
        );
    }
}
```

To send failures to WooCommerce > Status > Logs, where merchants look for them, wrap WooCommerce's
logger in a PSR-3 adapter:

```php
<?php

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

use Psr\Log\AbstractLogger;

final class WooLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        wc_get_logger()->log((string)$level, (string)$message, ['source' => 'polkurier']);
    }
}
```

Note that `$context` is not logged. The SDK puts the full request there, and the request body
contains the API token. Log only the message.

Placing the shipment
--------------------

Don't place it on `woocommerce_checkout_order_processed`, which runs during the customer's
request. Use Action Scheduler, which comes with WooCommerce:

```php
add_action('woocommerce_order_status_processing', static function (int $order_id): void {
    if (as_has_scheduled_action('polkurier_place_shipment', ['order_id' => $order_id])) {
        return;
    }

    as_enqueue_async_action('polkurier_place_shipment', ['order_id' => $order_id], 'polkurier');
});

add_action('polkurier_place_shipment', [ShipmentDispatcher::class, 'handle'], 10, 1);
```

```php
<?php

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Exception\ConnectionException;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Exception\ValidationException;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Exception\WebserviceException;

final class ShipmentDispatcher
{
    public static function handle(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (!$order || $order->get_meta('_polkurier_number')) {
            return;   // Gone, or already placed. Action Scheduler is at-least-once.
        }

        try {
            $created = Client::get()->orders()->createOrder((new OrderMapper())->toMessage($order));
        } catch (ValidationException | WebserviceException $e) {
            // Will fail the same way next time. Tell the merchant and stop.
            $order->add_order_note(sprintf(
                __('Polkurier rejected the shipment: %s', 'woo-polkurier'),
                $e->getMessage(),
            ));
            $order->save();

            return;
        } catch (ConnectionException $e) {
            // Unknown outcome. Let Action Scheduler retry. The guard above makes that safe.
            throw $e;
        }

        $order->update_meta_data('_polkurier_number', $created->getOrderNumber());
        $order->add_order_note(sprintf(
            __('Polkurier shipment %s created.', 'woo-polkurier'),
            $created->getOrderNumber(),
        ));
        $order->save();
    }
}
```

The two catch blocks handle different cases. If you retry a rejected payload 5 times, you get 5
identical failures and a confused merchant. After a lost connection, retrying is correct, and it's
safe only because of the meta check at the top.

Use `update_meta_data()` and `get_meta()`, not `update_post_meta()`. WooCommerce HPOS
(High-Performance Order Storage) moves orders out of `wp_posts`, and the CRUD methods work with both
storage types.

Mapping a WooCommerce order
---------------------------

```php
<?php

declare(strict_types=1);

namespace YourVendor\WooPolkurier;

use WC_Order;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Builder\Order\CreateOrderMessageBuilder;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Builder\Order\OrderAddressMessageBuilder;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Enum\Order\PackTypeEnum;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Request\Order\CreateOrderMessage;

final class OrderMapper
{
    public function toMessage(WC_Order $order): CreateOrderMessage
    {
        $recipient = OrderAddressMessageBuilder::create()
            ->person(trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()))
            ->street($order->get_shipping_address_1())
            ->houseNumber($order->get_shipping_address_2() ?: '1')
            ->city($order->get_shipping_city())
            ->postcode($order->get_shipping_postcode())
            ->phone($order->get_billing_phone())
            ->email($order->get_billing_email());

        if ($order->get_shipping_company()) {
            $recipient->company($order->get_shipping_company());
        }

        if ($pointId = $order->get_meta('_polkurier_point_id')) {
            $recipient->pointId($pointId);
        }

        $pack = PackMessageBuilder::create()
            ->length(30)->width(20)->height(15)
            ->weight($this->weightKg($order))
            ->type(PackTypeEnum::Standard)
            ->build();

        return CreateOrderMessageBuilder::create()
            ->courier((string)$order->get_meta('_polkurier_carrier'))
            ->shipmentType(ShipmentTypeEnum::Box)
            ->packs([$pack])
            ->sender((new WarehouseAddress())->build())
            ->recipient($recipient->build())
            ->description('#' . $order->get_order_number())
            ->build();
    }

    private function weightKg(WC_Order $order): float
    {
        $total = 0.0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if ($product && $product->get_weight()) {
                $total += (float)wc_get_weight((float)$product->get_weight(), 'kg') * $item->get_quantity();
            }
        }

        return max($total, 0.1);   // The API requires a positive weight.
    }
}
```

Don't skip `wc_get_weight()`. WooCommerce stores weights in the unit the shop has configured, and
the API expects kilograms. Convert dimensions the same way with `wc_get_dimension()`.

A shipping method
-----------------

```php
final class ShippingMethod extends \WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'polkurier';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Polkurier', 'woo-polkurier');
        $this->supports = ['shipping-zones', 'instance-settings'];

        $this->init();
    }

    public function calculate_shipping($package = []): void
    {
        // Price from your own table. Do NOT call orderValuation() here. This runs on
        // every cart change, and a third-party request here makes the cart feel broken.
        $this->add_rate([
            'id'    => $this->id,
            'label' => $this->title,
            'cost'  => $this->costFor($package),
        ]);
    }
}
```

If you need live quotes, cache them by destination and weight with a short TTL, and fall back to a
fixed cost when the API is slow or returns an error. If a shipping method throws, the cart page
breaks.

Pickup points at checkout
-------------------------

Fetch the list once and keep it in a transient:

```php
function polkurier_lockers(): array
{
    $cached = get_transient('polkurier_lockers');

    if (is_array($cached)) {
        return $cached;
    }

    try {
        $lockers = Client::get()->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));
    } catch (\Throwable) {
        return [];
    }

    set_transient('polkurier_lockers', $lockers, HOUR_IN_SECONDS);

    return $lockers;
}
```

There are tens of thousands of lockers, far too many for a `<select>`. Put them behind an AJAX
autocomplete, or use the map widget with `geo()->getMapToken()`.

Save the selected ID as order meta and read it back in the mapper, as shown above.

Labels from the orders list
---------------------------

`getLabel()` accepts a list, so a bulk action on the selected orders needs only one API call:

```php
add_filter('bulk_actions-woocommerce_page_wc-orders', static function (array $actions): array {
    $actions['polkurier_labels'] = __('Print Polkurier labels', 'woo-polkurier');

    return $actions;
});

add_filter('handle_bulk_actions-woocommerce_page_wc-orders', static function (string $redirect, string $action, array $ids): string {
    if ($action !== 'polkurier_labels') {
        return $redirect;
    }

    $numbers = array_values(array_filter(array_map(
        static fn(int $id): string => (string)wc_get_order($id)?->get_meta('_polkurier_number'),
        $ids,
    )));

    if ($numbers === []) {
        return add_query_arg('polkurier_error', 'none', $redirect);
    }

    $document = Client::get()->orders()->getLabel(new GetDocumentMessage(orderNo: $numbers));

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="polkurier-labels.pdf"');
    echo $document->getDecodedFile();
    exit;
}, 10, 3);
```

`wc-orders` is the HPOS screen ID. If you support legacy storage, also add the `edit-shop_order`
variants.

Site Health
-----------

Show the API status in Site Health, where the merchant will see it:

```php
add_filter('site_status_tests', static function (array $tests): array {
    $tests['direct']['polkurier'] = [
        'label' => __('Polkurier API', 'woo-polkurier'),
        'test'  => static function (): array {
            try {
                Client::get()->utils()->heartbeat();

                return ['status' => 'good', 'label' => __('Polkurier is reachable', 'woo-polkurier')];
            } catch (\Throwable $e) {
                return ['status' => 'critical', 'label' => $e->getMessage()];
            }
        },
    ];

    return $tests;
});
```

`heartbeat()` doesn't send credentials, so this test checks the connection and ignores the token.

What to watch out for
---------------------

- **Prefix the vendor tree.** If a plugin works on its own but causes a fatal error next to another
  plugin, this is usually why.
- **Don't call the API during checkout.** Use Action Scheduler.
- **Use `$order->get_meta()`, not `get_post_meta()`,** so the code works with HPOS.
- **Don't log the SDK's context array.** It contains the request body, and the body contains the
  token.
- **Use `declare(strict_types=1)` only in your own files.** WordPress often passes strings where
  integers are expected, so cast values where they cross into your code.
