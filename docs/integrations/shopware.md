Shopware 6
==========

For Shopware 6.5.8, 6.6.10.12 and 6.7.7 or later, as a plugin. Shopware runs on Symfony, so most
of [the Symfony page](symfony.md) applies here too.

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
        "shopware/core": "~6.5.8 || ~6.6.10.12 || ~6.7.7",
        "polkurier/polkurier-sdk": "^2.0"
    },
    "extra": {
        "shopware-plugin-class": "YourVendor\\PolkurierShipping\\PolkurierShipping",
        "label": {"en-GB": "Polkurier shipping", "de-DE": "Polkurier Versand"}
    },
    "autoload": {"psr-4": {"YourVendor\\PolkurierShipping\\": "src/"}}
}
```

Shopware merges plugin dependencies into the project's Composer tree, so nothing needs prefixing.
The SDK's Symfony components accept 6.4, 7.4 and 8.x. Shopware ships Symfony 6.4 from 6.5.8, and
7.4 from 6.6.10.12 and 6.7.7; Composer refuses the SDK on earlier releases.

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

The values differ per sales channel, so the plugin implements [`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php) instead of
using a fixed [`Config`](../../src/Config.php):

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

A message handler has no current sales channel to fall back on. `SalesChannelConfigProvider::forSalesChannel()` returns a copy
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

`polkurier.metadata_cache` is a Symfony cache pool and `polkurier.logger` a Monolog channel, both
defined in the plugin's `packages` configuration. Both are standard Symfony configuration, and
Shopware loads the plugin's `Resources/config/packages/*.yaml` files.

Sales-channel scoping
---------------------

The SDK asks the provider for its configuration on every call. The provider above reads the
settings of the sales channel it is bound to with `SalesChannelConfigProvider::forSalesChannel()`,
and the settings for all sales channels when it is unbound. In a **message handler or a scheduled
task** there is no current channel to bind it to.

There, the client is built for each message, for the channel the message names:

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

The serializer is expensive to build and keeps no state between calls, so one is shared. The client
is quick to build and belongs to one channel, so there is one per message.

Placing the shipment from the message bus
-----------------------------------------

The plugin subscribes to the order transaction state change, not to order placement, and
dispatches a message:

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

A **Flow Builder** action lets the merchant decide when a shipment is created instead:

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

Shopware's message bus delivers at least once, and the SDK has no idempotency key. The handler
checks its own record first:

```php
if ($order->getCustomFields()['polkurier_number'] ?? null) {
    return;
}
```

The number can live in a custom field or in a plugin entity keyed by order ID. Custom fields are
convenient but not indexed.

Error handling in a handler
---------------------------

```php
use Shopware\Core\Framework\MessageQueue\Handler\RetryableMessageHandlerInterface;

try {
    $created = $client->orders()->createOrder($this->mapper->toMessage($order));
} catch (ValidationException | WebserviceException $e) {
    // A rejected payload will be rejected again, so it is recorded and not rethrown.
    $this->recordFailure($order, $e->getMessage());

    return;
} catch (ConnectionException $e) {
    // Unknown outcome. Rethrow so the bus retries. The guard above makes that safe.
    throw $e;
}
```

By default, Shopware retries anything that throws. A permanent failure that is rethrown ends up in
the dead-letter queue, where a bad postcode looks like an outage, so the handler catches those.

A shipping method
-----------------

The plugin registers a `shipping_method` on install and prices it with a Shopware rule-based price
matrix. Calling [`OrdersServiceInterface::orderValuation()`](../../src/Api/Service/OrdersServiceInterface.php) from the cart would mean a third-party request on
every line item change.

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

Besides [webhooks](../webhooks.md), status changes can be polled in a scheduled task:

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

For each sales channel, the handler builds a client and pages through [`OrdersServiceInterface::getOrders()`](../../src/Api/Service/OrdersServiceInterface.php) filtered to a
recent date range. Each page covers up to 100 orders, where
[`OrdersServiceInterface::getStatus()`](../../src/Api/Service/OrdersServiceInterface.php) would take a call per order.

Caching
-------

Pickup point lists, supported countries and system limits can live in Shopware's cache pool:

```php
$item = $this->cache->getItem('polkurier.inpost.lockers');

if (!$item->isHit()) {
    $item->set($client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT'])));
    $item->expiresAfter(3600);
    $this->cache->save($item);
}
```

Tagged entries are removed by `cache:clear`. The serializer's metadata has its own pool, cleared on
deploy, because the metadata cache key doesn't include a version.

Administration module
---------------------

Labels can be printed from an administration extension that calls an API controller in the plugin:

```php
#[Route(defaults: ['_routeScope' => ['api']])]
final class LabelController extends AbstractController
{
    #[Route(path: '/api/_action/polkurier/labels', name: 'api.action.polkurier.labels', methods: ['POST'])]
    public function labels(Request $request): Response
    {
        $numbers = $request->request->all('numbers');

        $document = $this->orders->getLabel(new GetDocumentMessage(orderNo: $numbers));

        return new Response($document->file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="labels.pdf"',
        ]);
    }
}
```

[`OrdersServiceInterface::getLabel()`](../../src/Api/Service/OrdersServiceInterface.php) accepts a list, so printing labels for several orders selected in the grid takes one
call.
