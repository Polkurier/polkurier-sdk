WooCommerce / WordPress
=======================

For WooCommerce 8+ on PHP 8.3+, as a plugin. Of all the hosts here, WordPress is the hardest fit
for the SDK. It has no container and no autoloader of its own, and it does very little type
checking.

Autoloading, and the dependency problem
---------------------------------------

WordPress loads all active plugins into one process that shares one global class table. If two
plugins ship different versions of `symfony/serializer`, the site hits a fatal error, and you can't
control what else the merchant has installed.

At build time, [PHP-Scoper](https://github.com/humbug/php-scoper) or [Mozart](https://github.com/coenjacobs/mozart)
can move the SDK and its dependencies into your own namespace:

```json
{
    "name": "your-vendor/woo-polkurier",
    "require": {
        "php": "~8.3.0||~8.4.0||~8.5.0",
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

Without it, a distributed plugin works on your machine and breaks on any site that also runs a
plugin with an older Symfony.

Plugin bootstrap
----------------

```php
<?php
/**
 * Plugin Name: Polkurier for WooCommerce
 * Requires PHP: 8.3
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

[`WC_Integration`](https://woocommerce.github.io/code-reference/classes/WC-Integration.html) adds a settings tab under WooCommerce > Settings > Integration. WooCommerce
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

> WordPress stores options in plain text. Secrets usually go in `wp-config.php`, and a constant that
> overrides the setting lets a site keep the token out of the database:
>
> ```php
> $token = defined('POLKURIER_TOKEN') ? POLKURIER_TOKEN : $settings['token'];
> ```

Building the client
-------------------

WordPress has no container, so the client is a lazy singleton:

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
    // Untyped, because psr/log 1.x and 2.x declare the parameter without a type and a child
    // may not narrow it.
    public function log($level, $message, array $context = []): void
    {
        wc_get_logger()->log((string)$level, (string)$message, ['source' => 'polkurier']);
    }
}
```

`$context` is not logged. The request and response in it are prepared like the message, but the
exceptions in it can hold the full request, API token included.

Placing the shipment
--------------------

`woocommerce_checkout_order_processed` runs during the customer's request, so the shipment is
placed from Action Scheduler, which comes with WooCommerce:

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

        $order->update_meta_data('_polkurier_number', $created->orderNumber);
        $order->add_order_note(sprintf(
            __('Polkurier shipment %s created.', 'woo-polkurier'),
            $created->orderNumber,
        ));
        $order->save();
    }
}
```

The two catch blocks handle different cases. If you retry a rejected payload 5 times, you get 5
identical failures and a confused merchant. After a lost connection, retrying is correct, and it's
safe only because of the meta check at the top.

The code uses [`WC_Data::update_meta_data()`](https://woocommerce.github.io/code-reference/classes/WC-Data.html) and [`WC_Data::get_meta()`](https://woocommerce.github.io/code-reference/classes/WC-Data.html), not
[`update_post_meta()`](https://developer.wordpress.org/reference/functions/update_post_meta/). WooCommerce HPOS
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
use YourVendor\WooPolkurier\Vendor\Polkurier\Webservice\Entity\Order\CreateOrderMessage;

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

[`wc_get_weight()`](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-formatting-functions.html) converts the weight: WooCommerce stores weights in the unit the shop has
configured, and the API expects kilograms. [`wc_get_dimension()`](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-formatting-functions.html) converts dimensions the same way.

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
        // Priced from the shop's own table. This runs on every cart change, so a call to
        // orderValuation() here would make every cart update wait on a third-party request.
        $this->add_rate([
            'id'    => $this->id,
            'label' => $this->title,
            'cost'  => $this->costFor($package),
        ]);
    }
}
```

A live quote makes the cart wait on the API, and a shipping method that throws breaks the cart
page.

Pickup points at checkout
-------------------------

The list is fetched once and kept in a transient:

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

There are tens of thousands of lockers, far too many for a `<select>`. An AJAX autocomplete or the
map widget with [`$client->geo()->getMapToken()`](../../src/Api/Service/GeoServiceInterface.php) handles that many.

The selected ID is saved as order meta and read back in the mapper, as shown above.

Labels from the orders list
---------------------------

[`OrdersServiceInterface::getLabel()`](../../src/Api/Service/OrdersServiceInterface.php) accepts a list, so a bulk action on the selected orders needs only one API call:

```php
add_filter('bulk_actions-woocommerce_page_wc-orders', static function (array $actions): array {
    $actions['polkurier_labels'] = __('Print Polkurier labels', 'woo-polkurier');

    return $actions;
});

add_filter('handle_bulk_actions-woocommerce_page_wc-orders', static function (
    string $redirect,
    string $action,
    array $ids,
): string {
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
    echo $document->file;
    exit;
}, 10, 3);
```

`wc-orders` is the HPOS screen ID. Legacy storage uses the `edit-shop_order` variants.

Site Health
-----------

The API status can be shown in Site Health, where the merchant sees it:

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

[`UtilsServiceInterface::heartbeat()`](../../src/Api/Service/UtilsServiceInterface.php) doesn't send credentials, so this test checks the connection and ignores the token.
