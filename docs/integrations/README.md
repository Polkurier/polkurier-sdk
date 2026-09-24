Integrations
============

How to wire this SDK into the frameworks and platforms people actually run shops on.

| Frameworks | Platforms |
|------------|-----------|
| [Symfony](symfony.md) | [Magento 2 / Adobe Commerce](magento2.md) |
| [Laravel](laravel.md) | [PrestaShop](prestashop.md) |
| [Laminas MVC and Mezzio](laminas-mezzio.md) | [WooCommerce / WordPress](woocommerce.md) |
| [Slim, PHP-DI and any PSR-11 container](psr-11.md) | [Shopware 6](shopware.md) |
| [Plain PHP, no container](psr-11.md#no-container-at-all) | [Sylius](sylius.md) |

These are patterns, not packages. There is no `polkurier/polkurier-sdk-bundle` and no Magento
module in this repository — what follows is the wiring, in each host's own idiom, that you drop
into your own project or module.

---

What every integration has to do
--------------------------------

The same five things, in every host. The pages differ in syntax, not in substance.

### 1. Make the configuration a service

`Config` needs credentials, and credentials belong in the host's own configuration mechanism —
environment variables, a settings table, an admin screen — never in code. Every page below starts
here, because it is the part that differs most.

Three depths, from [Configuration](../configuration.md):

- a `Config` built from values read at boot;
- your own `ConfigInterface`, when the values come from somewhere that is not a constructor call;
- your own `ConfigProviderInterface`, when they are not known until the call is made — per shop,
  per tenant, per sales channel.

Multi-store platforms (Magento, Shopware, PrestaShop multistore, Sylius channels) usually need the
third.

### 2. Share one serializer

`SerializerProvider` is the expensive object. Register it as a singleton and give it a PSR-6 cache
pool — every framework below has one already.

```php
new SerializerProvider(new SerializerConfig(metadataCache: $pool));
```

In a long-running worker this matters a great deal; in classic PHP-FPM it still saves the
reflection pass on every class you touch.

### 3. Register the services you actually use

Two approaches, and both are legitimate:

**Register the facade.** One service, sixteen accessors, lazy construction. Least wiring; your
code depends on `PolkurierWebservice`.

```php
$client->orders()->createOrder($message);
```

**Register the services individually.** Your code depends on
`Api\Service\OrdersServiceInterface` and nothing else — which is the better shape for testing, and
what the autowiring in Symfony, Laravel and Sylius makes almost free.

```php
final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}
}
```

Most pages below show both, because which is right depends on how much of the API you touch. A
module that only places orders should inject one interface; an admin panel that touches nine
services is better off with the facade.

### 4. Give it the host's logger

Every framework here has a PSR-3 logger. Pass it, and mind the warning: request bodies contain the
API token. See [Configuration — logging](../configuration.md#logging).

```php
new Config(/* … */, methodsToLog: [ApiMethodEnum::CreateOrder]);
```

Leaving `methodsToLog` empty logs errors only, which is the right default for production.

### 5. Decide about the HTTP client

The SDK builds its own Guzzle client by default, which is fine. Replace
`ClientFactoryInterface` when the host has a client you would rather use — for its instrumentation,
its proxy configuration, or its retry policy. Magento and Shopware both have reasons to; a plain
Symfony application usually does not.

---

Choosing where the SDK runs
---------------------------

Applies to every platform below, and gets more wrong than the wiring does.

**Never call the API from a page render.** Placing an order, fetching a label and booking a
collection are all seconds-scale calls against a third party. Put them in the platform's queue —
Symfony Messenger, Laravel queues, Magento's consumers, Shopware's message bus, WordPress's Action
Scheduler — and have the storefront show a pending state.

**Cache the stable reads.** Pickup point listings, supported countries, contact topics and system
limits change on the scale of hours. [Recipes — caching](../recipes.md#caching-what-does-not-change)
lists them with typical lifetimes. Fetching 30,000 InPost lockers on a checkout page is the single
most common way to make an integration feel broken.

**Reconcile rather than retry.** There is no idempotency key on `create_order`. A timed-out create
must be reconciled against `get_orders` before it is sent again — see
[Errors — retrying safely](../errors.md#retrying-safely). In a queue-based integration this is a
job that checks before it acts, not a `retry: 3` in the worker configuration.

**Store the order number.** `CreatedOrderMessage::getOrderNumber()` is your handle on everything
afterwards: labels, protocols, status, cancellation. Persist it against your own order the moment
it comes back, in the same transaction if you can.

---

A shape that works everywhere
-----------------------------

Regardless of host, the integration that ages well has three layers:

```
your domain          Shipment, Parcel, Recipient — your own types
      │
your adapter         maps your types to CreateOrderMessage, and Order back to yours
      │
Api\Service\*        the SDK's interfaces
```

The adapter is the whole integration. It is a plain class with no framework in it, it can be unit
tested against a mocked interface, and it is the only thing that has to change when either side
moves.

What does *not* age well is calling `$client->orders()->createOrder()` from a controller, a
plugin hook or an observer with the mapping inline. Every page below puts the mapping in its own
class for that reason.
