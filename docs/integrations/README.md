Integrations
============

How to set up the SDK in the frameworks and shop platforms it is most often used with.

| Frameworks | Platforms |
|------------|-----------|
| [Symfony](symfony.md) | [Magento 2 / Adobe Commerce](magento2.md) |
| [Laravel](laravel.md) | [PrestaShop](prestashop.md) |
| [Laminas MVC and Mezzio](laminas-mezzio.md) | [WooCommerce / WordPress](woocommerce.md) |
| [Slim, PHP-DI and any PSR-11 container](psr-11.md) | [Shopware 6](shopware.md) |
| [Plain PHP, no container](psr-11.md#no-container-at-all) | [Sylius](sylius.md) |

None of these pages ships a package. This repository has no `polkurier/polkurier-sdk-bundle` and
no Magento module. Each page shows the wiring in the host's own style, for you to copy into your
project or module.

---

What every integration has to do
--------------------------------

Every host needs the same five steps. Only the syntax changes from page to page.

### 1. Make the configuration a service

[`Config`](../../src/Config.php) needs credentials. Keep them in the host's configuration mechanism (environment
variables, a settings table, an admin screen) and never in code. Each page starts with this step
because it varies the most between hosts.

[Configuration](../configuration.md) describes three levels:

- a [`Config`](../../src/Config.php) built from values read at boot
- your own [`ConfigInterface`](../../src/Api/ConfigInterface.php), when the values don't come from a constructor call
- your own [`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php), when the values are only known at call time (per shop, per
  tenant or per sales channel)

Multi-store platforms (Magento, Shopware, PrestaShop multistore, Sylius channels) usually need the
third one.

### 2. Share one serializer

[`SerializerProvider`](../../src/Serializer/SerializerProvider.php) is expensive to build. Register it as a singleton and give it a PSR-6 cache
pool. All the frameworks below already have one.

```php
new SerializerProvider(new SerializerConfig(metadataCache: $pool));
```

This matters most in long-running workers. Under classic PHP-FPM it still saves a reflection pass
on each class the request uses.

### 3. Register the services you use

You can do this in two ways.

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

Most pages show both. Pick based on how much of the API you use: a module that only places orders
should inject one interface, while an admin panel that uses 9 services is simpler with the facade.

### 4. Give it the host's logger

All the frameworks here provide a PSR-3 logger, so pass it in. Be careful: request bodies contain
the API token. See [Configuration: logging](../configuration.md#logging).

```php
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

With `methodsToLog` left empty, only errors are logged. Use that in production.

### 5. Decide about the HTTP client

By default the SDK builds its own Guzzle client, and that works fine. Replace
[`ClientFactoryInterface`](../../src/Http/ClientFactoryInterface.php) if the host already has a client you want to use, e.g. for its
instrumentation, proxy settings or retry policy. On Magento and Shopware there are good reasons
to. A plain Symfony application usually doesn't need to.

---

Choosing where the SDK runs
---------------------------

This applies to every platform below. People get it wrong more often than the wiring.

**Don't call the API while rendering a page.** Placing an order, fetching a label and booking a
collection are calls to a third party that can take seconds. Put them on the platform's queue
(Symfony Messenger, Laravel queues, Magento consumers, the Shopware message bus, WordPress Action
Scheduler) and show a pending state in the storefront.

**Cache the reads that rarely change.** Pickup point lists, supported countries, contact topics and
system limits change over hours. [Recipes: caching](../recipes.md#caching-what-does-not-change)
lists them with typical lifetimes. Loading 30,000 InPost lockers on the checkout page is the most
common reason an integration feels broken.

**Reconcile before you retry.** `create_order` has no idempotency key. If a create times out, check
`get_orders` before sending it again (see
[Errors: retrying safely](../errors.md#retrying-safely)). In a queue-based integration, write the
job so it checks first and then acts. Setting `retry: 3` in the worker configuration is not enough.

**Store the order number.** [`CreatedOrderMessage::getOrderNumber()`](../../src/Response/Order/CreatedOrderMessage.php) is what you use for everything
afterwards: labels, protocols, status and cancellation. Save it against your own order as soon as
it comes back, in the same transaction if you can.

---

A structure that works everywhere
---------------------------------

Whatever the host, integrations that stay maintainable have three layers:

```
your domain          Shipment, Parcel, Recipient (your own types)
      │
your adapter         maps your types to CreateOrderMessage, and Order back to yours
      │
Api\Service\*        the SDK's interfaces
```

Most of the integration lives in the adapter. It is a plain class with no framework code, you can
unit test it against a mocked interface, and it is the only part that changes when either side
does.

Avoid calling [`$client->orders()->createOrder()`](../../src/Api/Service/OrdersServiceInterface.php) from a controller, plugin hook or observer with
the mapping written inline. Every page below keeps the mapping in its own class.
