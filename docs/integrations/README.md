Integrations
============

Polkurier's own Magento module is built on this SDK, so the [Magento page](magento2.md) describes
that module instead of the wiring.

| Frameworks | Platforms |
|------------|-----------|
| [Symfony](symfony.md) | [Magento 2 / Adobe Commerce](magento2.md) (Polkurier's module) |
| [Laravel](laravel.md) | [PrestaShop](prestashop.md) |
| [Laminas MVC and Mezzio](laminas-mezzio.md) | [WooCommerce / WordPress](woocommerce.md) |
| [Slim, PHP-DI and any PSR-11 container](psr-11.md) | [Shopware 6](shopware.md) |
| [Plain PHP, no container](psr-11.md#no-container-at-all) | [Sylius](sylius.md) |

Apart from the Magento page, none of these pages ships a package. This repository has no
`polkurier/polkurier-sdk-bundle`. Each page shows the wiring in the host's own style, for you to
copy into your project or module.

---

What every integration has to do
--------------------------------

### 1. Make the configuration a service

[`Config`](../../src/Config.php) needs credentials, which come from the host's configuration mechanism (environment
variables, a settings table, an admin screen).

[Configuration](../configuration.md) describes three levels:

- a [`Config`](../../src/Config.php) built from values read at boot
- your own [`ConfigInterface`](../../src/Api/ConfigInterface.php), when the values don't come from a constructor call
- your own [`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php), when the values are only known at call time (per shop, per
  tenant or per sales channel)

Multi-store platforms (Shopware, PrestaShop multistore, Sylius channels) usually need the
third one.

### 2. Share one serializer

[`SerializerProvider`](../../src/Serializer/SerializerProvider.php) is expensive to build, so it is registered as a singleton with a PSR-6
cache pool. All the frameworks below have a pool.

```php
new SerializerProvider(new SerializerConfig(metadataCache: $pool));
```

This matters most in long-running workers. Under classic PHP-FPM it still saves a reflection pass
on each class the request uses.

### 3. Register the services you use

Register the facade. That's one service with 16 accessors, built lazily. It needs the least wiring,
and your code depends on [`PolkurierWebservice`](../../src/PolkurierWebservice.php).

```php
$client->orders()->createOrder($message);
```

Or register the services one by one. Your code then depends only on
[`Api\Service\OrdersServiceInterface`](../../src/Api/Service/OrdersServiceInterface.php), which is easier to test. With autowiring in Symfony, Laravel
and Sylius this takes very little extra setup.

```php
final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}
}
```

A module that only places orders needs one interface, while an admin panel
that uses 9 services is simpler with the facade.

### 4. Give it the host's logger

All the frameworks here provide a PSR-3 logger, which the SDK takes as its logger. It hides secrets
and personal data in what it logs, and leaves files out, unless you allow them. See
[Configuration: logging](../configuration.md#logging).

```php
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

With `methodsToLog` left empty, only errors are logged.

### 5. Decide about the HTTP client

By default the SDK builds its own Guzzle client. Replacing
[`ClientFactoryInterface`](../../src/Http/ClientFactoryInterface.php) lets the host's own client make the calls, e.g. for its
instrumentation, proxy settings or retry policy.
