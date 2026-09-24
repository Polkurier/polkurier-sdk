WooCommerce / WordPress
=======================

WooCommerce 8+ on PHP 8.4+, as a plugin. This is the host that fits the SDK least comfortably —
WordPress has no container, no autoloader of its own, and no type system to speak of — so most of
this page is about keeping the two apart.

Autoloading, and the dependency problem
---------------------------------------

WordPress loads every active plugin into one process with one global class table. Two plugins
shipping different versions of `symfony/serializer` is a fatal error, and you cannot control what
else the merchant has installed.

**Prefix your dependencies.** Use [PHP-Scoper](https://github.com/humbug/php-scoper) or
[Mozart](https://github.com/coenjacobs/mozart) at build time to move the SDK and its dependencies
into your own namespace:

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

This is not optional in a plugin you distribute. Skipping it works on your machine and breaks on a
site that also runs a plugin with an older Symfony.

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

The `plugins_loaded` priority of 20 is deliberate: WooCommerce loads at 10, and the settings API
below needs it present.

Settings
--------

`WC_Integration` gives you a settings tab under WooCommerce > Settings > Integration, with
WooCommerce's own field rendering and saving:

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

Register it:

```php
add_filter('woocommerce_integrations', static function (array $integrations): array {
    $integrations[] = Settings::class;

    return $integrations;
});
```

> WordPress stores options in plain text, and `wp-config.php` is the conventional place for real
> secrets. Offer a constant override so a security-conscious site can keep the token out of the
> database:
>
> ```php
> $token = defined('POLKURIER_TOKEN') ? POLKURIER_TOKEN : $settings['token'];
> ```

Building the client
-------------------

No container, so a lazy singleton:

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

A PSR-3 adapter over WooCommerce's own logger, so failures land where a merchant will find them
(WooCommerce > Status > Logs):

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

Note that `$context` is dropped. That is deliberate — the SDK puts the full request in there, and
its body carries the API token. Log the message only.

Placing the shipment
--------------------

Never on `woocommerce_checkout_order_processed`; that is the customer's request. Use Action
Scheduler, which ships with WooCommerce:

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
            // Unknown outcome. Let Action Scheduler retry; the guard above makes that safe.
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

The distinction between the two catch blocks is the whole point. A rejected payload retried five
times is five identical failures and a confused merchant; a lost connection retried is the right
behaviour, and safe only because of the meta check at the top.

Use `update_meta_data()` / `get_meta()` rather than `update_post_meta()` — WooCommerce's HPOS
(High-Performance Order Storage) moves orders out of `wp_posts`, and the CRUD methods work under
both.

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

`wc_get_weight()` matters: WooCommerce stores weights in whatever unit the shop configured, and the
API wants kilograms. Same for dimensions, via `wc_get_dimension()`.

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
        // Price from your own table. Do NOT call orderValuation() here — this runs on
        // every cart change, and a third-party round trip in the cart is a broken shop.
        $this->add_rate([
            'id'    => $this->id,
            'label' => $this->title,
            'cost'  => $this->costFor($package),
        ]);
    }
}
```

If you must quote live, cache on destination plus weight with a short TTL, and fall back to a fixed
cost when the API is slow or refuses. A shipping method that throws takes the cart page with it.

Pickup points at checkout
-------------------------

Fetch the listing once into a transient, not per page load:

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

There are tens of thousands of these. Serving them all into a `<select>` is worse than useless —
put them behind an AJAX autocomplete, or use the map widget via `geo()->getMapToken()`.

Store the chosen id as order meta and read it back in the mapper, as above.

Labels from the orders list
---------------------------

`getLabel()` takes a list, so a bulk action over selected orders is one API call:

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

The `wc-orders` screen id is the HPOS one; add the `edit-shop_order` variants too if you support
legacy storage.

Site Health
-----------

Surface reachability where a merchant will see it:

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

`heartbeat()` sends no credentials, so this reports the network rather than the token — which is
what a health check should do.

What to watch out for
---------------------

- **Prefix the vendor tree.** Not doing this is the most common cause of a plugin that works alone
  and fatals alongside another.
- **Never call the API during checkout.** Action Scheduler exists for this.
- **`$order->get_meta()`, not `get_post_meta()`.** HPOS.
- **Do not log the SDK's context array.** It contains the request body, and the body contains the
  token.
- **`declare(strict_types=1)` in your own files only.** WordPress passes strings where integers are
  expected all over; keep the boundary tidy and cast at it.
