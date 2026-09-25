Shopware 6
==========

For Shopware 6.5 and 6.6, as a plugin. Shopware runs on Symfony, so most of
[the Symfony page](symfony.md) applies here too. This page covers the parts specific to Shopware:
plugin configuration, sales channel scoping, the message bus and the Flow Builder.

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

Shopware merges plugin dependencies into the project's Composer tree, so you don't need to prefix
anything. Do check that the project's `symfony/serializer` satisfies `^7.2`, though. Shopware 6.6
ships Symfony 6.4, and the SDK requires 7.x components. If they conflict, run
`composer require symfony/serializer:^7.2` at project level, if Shopware's own constraints allow
it. If they don't, run the SDK behind an HTTP service of your own.

Configuration
-------------

`src/Resources/config/config.xml` creates a settings screen. Its values are scoped per sales
channel by default:

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

The values differ per sales channel, so implement `ConfigProviderInterface` instead of using a
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

A message handler has no current sales channel to fall back on. `forSalesChannel()` returns a copy
bound to a given channel, so you can use the provider there too. See
[below](#sales-channel-scoping).

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

Define `polkurier.metadata_cache` as a Symfony cache pool and `polkurier.logger` as a Monolog
channel in the plugin's `packages` configuration. Both are standard Symfony configuration, and
Shopware loads the plugin's `Resources/config/packages/*.yaml` files.

Sales-channel scoping
---------------------

Shopware integrations most often go wrong here. `AbstractService` reads its configuration **once,
in the constructor**. A service taken from the container keeps the credentials of whichever sales
channel was current when it was built. In a storefront request that's what you want. In a
**message handler or a scheduled task** there is no current channel at all.

In those cases, build the client for each message:

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

The serializer is expensive to build and keeps no state between calls, so share it. The client is
quick to build and belongs to one channel, so create it per message.

Placing the shipment from the message bus
-----------------------------------------

Subscribe to the order transaction state change, not to order placement, and dispatch a message:

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

In practice you'll usually trigger it from the **Flow Builder** instead, so the merchant decides
when a shipment is created. Register a flow action:

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

The merchant can then set up a rule like "when order state becomes *in progress*, create a
Polkurier shipment" in the administration, without writing code.

Idempotency
-----------

Shopware's message bus delivers at least once, and the SDK has no idempotency key. Check your own
record first:

```php
if ($order->getCustomFields()['polkurier_number'] ?? null) {
    return;
}
```

Store the number in a custom field, or preferably in a plugin entity keyed by order ID. Custom
fields are convenient but not indexed. Once you have more than one field to store, use an entity.

Error handling in a handler
---------------------------

```php
use Shopware\Core\Framework\MessageQueue\Handler\RetryableMessageHandlerInterface;

try {
    $created = $client->orders()->createOrder($this->mapper->toMessage($order));
} catch (ValidationException | WebserviceException $e) {
    // A rejected payload will be rejected again. Record it and stop. Don't rethrow.
    $this->recordFailure($order, $e->getMessage());

    return;
} catch (ConnectionException $e) {
    // Unknown outcome. Rethrow so the bus retries. The guard above makes that safe.
    throw $e;
}
```

By default, Shopware retries anything that throws. Catch the permanent failures and don't
rethrow them. Otherwise a bad postcode ends up in the dead-letter queue and looks like an outage.

A shipping method
-----------------

Register a `shipping_method` on install and price it with a Shopware rule-based price matrix.
Avoid live quotes: calling `orderValuation()` from the cart means a third-party request on every
line item change.

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

`technicalName` is required from Shopware 6.6 on.

Scheduled tasks
---------------

The API has no callbacks, so poll for status changes in a scheduled task:

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

For each sales channel, the handler builds a client and pages through `getOrders()` filtered to a
recent date range. Don't call `getStatus()` for each order. One paged query covers hundreds of
orders.

Caching
-------

Keep pickup point lists, supported countries and system limits in Shopware's cache pool instead
of fetching them live:

```php
$item = $this->cache->getItem('polkurier.inpost.lockers');

if (!$item->isHit()) {
    $item->set($client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT'])));
    $item->expiresAfter(3600);
    $this->cache->save($item);
}
```

Tag the entries so `cache:clear` removes them. Give the serializer's metadata its own pool and
clear it on deploy, because the metadata cache key doesn't include a version.

Administration module
---------------------

To print labels, add an administration extension that calls an API controller in your plugin:

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

`getLabel()` accepts a list, so printing labels for several orders selected in the grid takes one
call.
