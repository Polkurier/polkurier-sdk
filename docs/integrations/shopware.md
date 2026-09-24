Shopware 6
==========

Shopware 6.5 / 6.6, as a plugin. Shopware is Symfony underneath, so most of
[the Symfony page](symfony.md) applies — this covers what is Shopware-specific: plugin
configuration, sales-channel scoping, the message bus and the flow builder.

The plugin
----------

```
custom/plugins/PolkurierShipping/
├── composer.json
├── src/
│   ├── PolkurierShipping.php
│   ├── Resources/config/
│   │   ├── services.xml
│   │   └── config.xml
│   ├── Service/
│   ├── MessageQueue/
│   └── Subscriber/
```

```json
{
    "name": "your-vendor/polkurier-shipping",
    "type": "shopware-platform-plugin",
    "require": {
        "shopware/core": "~6.6.0",
        "polkurier/polkurier-sdk": "^2.0"
    },
    "extra": {
        "shopware-plugin-class": "YourVendor\\PolkurierShipping\\PolkurierShipping",
        "label": {"en-GB": "Polkurier shipping", "de-DE": "Polkurier Versand"}
    },
    "autoload": {"psr-4": {"YourVendor\\PolkurierShipping\\": "src/"}}
}
```

Shopware merges plugin dependencies into the project's Composer tree, so no prefixing is needed —
but do check that the project's `symfony/serializer` satisfies `^7.2`. Shopware 6.6 ships Symfony
6.4, and the SDK requires 7.x components; where they conflict, the resolution is a project-level
`composer require symfony/serializer:^7.2` if Shopware's own constraints allow it, and otherwise
running the SDK behind an HTTP service of your own.

Configuration
-------------

`src/Resources/config/config.xml` gives you a settings screen, scoped per sales channel out of the
box:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/trunk/src/Core/System/SystemConfig/Schema/config.xsd">
    <card>
        <title>API</title>
        <input-field type="text">
            <name>login</name>
            <label>Customer ID</label>
        </input-field>
        <input-field type="password">
            <name>token</name>
            <label>API token</label>
        </input-field>
        <input-field type="bool">
            <name>sandbox</name>
            <label>Sandbox</label>
            <defaultValue>true</defaultValue>
        </input-field>
        <input-field type="int">
            <name>timeout</name>
            <label>Timeout (seconds)</label>
            <defaultValue>30</defaultValue>
        </input-field>
    </card>
</config>
```

Because the values are per sales channel, this is a `ConfigProviderInterface` case rather than a
fixed `Config`:

```php
<?php declare(strict_types=1);

namespace YourVendor\PolkurierShipping\Service;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class SalesChannelConfigProvider implements ConfigProviderInterface
{
    private const string PREFIX = 'PolkurierShipping.config.';

    private ?string $salesChannelId = null;

    public function __construct(
        private readonly SystemConfigService $systemConfig,
        private readonly string $shopwareVersion,
    ) {}

    public function forSalesChannel(?string $salesChannelId): self
    {
        $clone = clone $this;
        $clone->salesChannelId = $salesChannelId;

        return $clone;
    }

    public function getConfig(): ConfigInterface
    {
        return new Config(
            authorization: new AuthorizationMessage(
                token: (string)$this->systemConfig->get(self::PREFIX . 'token', $this->salesChannelId),
                login: (string)$this->systemConfig->get(self::PREFIX . 'login', $this->salesChannelId),
            ),
            platform: 'shopware6',
            platformVersion: $this->shopwareVersion,
            apiTimeout: (int)($this->systemConfig->get(self::PREFIX . 'timeout', $this->salesChannelId) ?: 30),
            sandbox: (bool)$this->systemConfig->get(self::PREFIX . 'sandbox', $this->salesChannelId),
        );
    }
}
```

The `forSalesChannel()` clone is what makes this usable from a message handler, where there is no
ambient sales-channel context — see [below](#sales-channel-scoping).

Services
--------

`src/Resources/config/services.xml`:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        <service id="YourVendor\PolkurierShipping\Service\SalesChannelConfigProvider">
            <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
            <argument>%kernel.shopware_version%</argument>
        </service>
        <service id="Polkurier\Webservice\Api\ConfigProviderInterface"
                 alias="YourVendor\PolkurierShipping\Service\SalesChannelConfigProvider"/>

        <!-- One serializer. Expensive to build, stateless, safe to share. -->
        <service id="Polkurier\Webservice\Serializer\SerializerConfig">
            <argument type="service" id="polkurier.metadata_cache"/>
        </service>
        <service id="Polkurier\Webservice\Serializer\SerializerProvider">
            <argument type="service" id="Polkurier\Webservice\Serializer\SerializerConfig"/>
        </service>
        <service id="Polkurier\Webservice\Serializer\SerializerProviderInterface"
                 alias="Polkurier\Webservice\Serializer\SerializerProvider"/>

        <service id="Polkurier\Webservice\Http\ClientFactory">
            <argument type="service" id="Polkurier\Webservice\Api\ConfigProviderInterface"/>
        </service>
        <service id="Polkurier\Webservice\Http\ClientFactoryInterface"
                 alias="Polkurier\Webservice\Http\ClientFactory"/>

        <service id="Polkurier\Webservice\Http\Request\RequestFactory">
            <argument type="service" id="Polkurier\Webservice\Serializer\SerializerProviderInterface"/>
            <argument type="service" id="Polkurier\Webservice\Api\ConfigProviderInterface"/>
        </service>
        <service id="Polkurier\Webservice\Http\Request\RequestFactoryInterface"
                 alias="Polkurier\Webservice\Http\Request\RequestFactory"/>

        <service id="Polkurier\Webservice\Http\Response\ResponseFactory">
            <argument type="service" id="Polkurier\Webservice\Serializer\SerializerProviderInterface"/>
        </service>
        <service id="Polkurier\Webservice\Http\Response\ResponseFactoryInterface"
                 alias="Polkurier\Webservice\Http\Response\ResponseFactory"/>

        <service id="Polkurier\Webservice\Api\Validation\ValidatorInterface">
            <factory class="Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory" method="create"/>
        </service>

        <service id="Polkurier\Webservice\Service\OrdersService">
            <argument type="service" id="Polkurier\Webservice\Http\Request\RequestFactoryInterface"/>
            <argument type="service" id="Polkurier\Webservice\Http\Response\ResponseFactoryInterface"/>
            <argument type="service" id="polkurier.logger"/>
            <argument type="service" id="Polkurier\Webservice\Http\ClientFactoryInterface"/>
            <argument type="service" id="Polkurier\Webservice\Api\ConfigProviderInterface"/>
            <argument type="service" id="Polkurier\Webservice\Api\Validation\ValidatorInterface"/>
        </service>
        <service id="Polkurier\Webservice\Api\Service\OrdersServiceInterface"
                 alias="Polkurier\Webservice\Service\OrdersService"/>
    </services>
</container>
```

Define `polkurier.metadata_cache` as a Symfony cache pool in the plugin's `packages` configuration,
and `polkurier.logger` as a Monolog channel — both are ordinary Symfony configuration, and Shopware
loads plugin `Resources/config/packages/*.yaml`.

Sales-channel scoping
---------------------

This is where a Shopware integration goes wrong. `AbstractService` reads its configuration **once,
in its constructor**, so a service resolved from the container holds whichever sales channel's
credentials were current at that moment. In a storefront request that is correct. In a
**message handler or a scheduled task** there is no ambient channel at all.

So in those contexts, build the client per message:

```php
<?php declare(strict_types=1);

namespace YourVendor\PolkurierShipping\MessageQueue;

use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use YourVendor\PolkurierShipping\Service\SalesChannelConfigProvider;

#[AsMessageHandler]
final class PlaceShipmentHandler
{
    public function __construct(
        private readonly SalesChannelConfigProvider $configProvider,
        private readonly SerializerProviderInterface $serializer,   // shared: stateless
        private readonly OrderMapper $mapper,
        private readonly EntityRepository $orderRepository,
    ) {}

    public function __invoke(PlaceShipment $message): void
    {
        $client = new PolkurierWebservice(
            config: $this->configProvider->forSalesChannel($message->salesChannelId)->getConfig(),
            serializerProvider: $this->serializer,
        );

        // … map and place …
    }
}
```

Sharing the serializer while rebuilding the client gets both correctness and speed: the serializer
is the expensive object and has no per-call state, while the client is cheap and channel-specific.

Placing the shipment from the message bus
-----------------------------------------

Subscribe to the order transaction state change rather than to order placement, and dispatch:

```php
<?php declare(strict_types=1);

namespace YourVendor\PolkurierShipping\Subscriber;

use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class OrderSubscriber implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $bus) {}

    public static function getSubscribedEvents(): array
    {
        return [OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten'];
    }

    public function onOrderWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getIds() as $id) {
            $this->bus->dispatch(new PlaceShipment($id, $event->getContext()->getSource()));
        }
    }
}
```

In practice the trigger is usually the **Flow Builder** instead, so the merchant decides when a
shipment is created rather than you. Register a flow action:

```php
<?php declare(strict_types=1);

namespace YourVendor\PolkurierShipping\Flow;

use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;

final class CreateShipmentAction extends FlowAction
{
    public static function getName(): string
    {
        return 'action.polkurier.create_shipment';
    }

    public function requirements(): array
    {
        return [OrderAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        $this->bus->dispatch(new PlaceShipment($flow->getOrderId(), $flow->getContext()));
    }
}
```

That gives the merchant "when order state becomes *in progress*, create a Polkurier shipment" in the
administration, without a line of code from them.

Idempotency
-----------

Shopware's message bus is at-least-once and the SDK has no idempotency key, so guard on your own
record:

```php
if ($order->getCustomFields()['polkurier_number'] ?? null) {
    return;
}
```

Store the number in a custom field or, better, in a plugin entity keyed by order id. Custom fields
are convenient and unindexed; an entity is the right answer once you have more than one field to
keep.

Error handling in a handler
---------------------------

```php
use Shopware\Core\Framework\MessageQueue\Handler\RetryableMessageHandlerInterface;

try {
    $created = $client->orders()->createOrder($this->mapper->toMessage($order));
} catch (ValidationException | WebserviceException $e) {
    // A rejected payload will be rejected again. Record it and stop; do not rethrow.
    $this->recordFailure($order, $e->getMessage());

    return;
} catch (ConnectionException $e) {
    // Unknown outcome. Rethrow so the bus retries — safe because of the guard above.
    throw $e;
}
```

Shopware's default retry configuration will retry anything that throws. Swallowing the permanent
failures is what stops a bad postcode from looking like an outage in the dead-letter queue.

A shipping method
-----------------

Register a `shipping_method` on install and price it with a Shopware rule-based price matrix, not
with a live quote. `orderValuation()` in the cart is a third-party round trip on every line item
change.

```php
public function install(InstallContext $context): void
{
    $this->shippingMethodRepository->create([[
        'name' => 'Polkurier',
        'technicalName' => 'shipping_polkurier',
        'active' => false,      // the merchant enables it after configuring credentials
        'deliveryTimeId' => $this->defaultDeliveryTimeId($context->getContext()),
        'prices' => [/* … */],
    ]], $context->getContext());
}
```

`technicalName` is required from Shopware 6.6.

Scheduled tasks
---------------

Polling statuses is a scheduled task rather than a webhook, since the API has no callbacks:

```php
<?php declare(strict_types=1);

namespace YourVendor\PolkurierShipping\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

final class SyncStatusesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'polkurier.sync_statuses';
    }

    public static function getDefaultInterval(): int
    {
        return 900;   // 15 minutes
    }
}
```

The handler pages through `getOrders()` filtered on a recent date window, per sales channel,
building a client for each. Do not call `getStatus()` per order — one paged query covers hundreds.

Caching
-------

Pickup point listings, supported countries and system limits belong in Shopware's cache pool, not
in a live call:

```php
$item = $this->cache->getItem('polkurier.inpost.lockers');

if (!$item->isHit()) {
    $item->set($client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT'])));
    $item->expiresAfter(3600);
    $this->cache->save($item);
}
```

Tag them so `cache:clear` reaches them, and give the serializer's metadata pool a separate entry
that your deploy clears — metadata is keyed by class, not by version.

Administration module
---------------------

For label printing, add an administration extension that calls a plugin API controller:

```php
#[Route(defaults: ['_routeScope' => ['api']])]
final class LabelController extends AbstractController
{
    #[Route(path: '/api/_action/polkurier/labels', name: 'api.action.polkurier.labels', methods: ['POST'])]
    public function labels(Request $request): Response
    {
        $numbers = $request->request->all('numbers');

        $document = $this->orders->getLabel(new GetDocumentMessage(orderNo: $numbers));

        return new Response($document->getDecodedFile(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="labels.pdf"',
        ]);
    }
}
```

`getLabel()` takes a list, so a bulk selection in the order grid is one call.
