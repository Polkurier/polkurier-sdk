Sylius
======

For Sylius 1.13 and 2.x. Sylius is a Symfony application, so start with
[the Symfony page](symfony.md). Everything there applies as is. This page covers the parts specific
to Sylius: credentials per channel, shipping methods and gateway configuration, the state machine
and shipment export.

The plugin skeleton
-------------------

A Sylius plugin is a regular bundle. All the code below goes into one:

```
src/
├── PolkurierShippingPlugin.php
├── DependencyInjection/
├── Resources/config/services.xml
├── Shipping/          ← gateway, mapper
├── StateMachine/      ← callbacks
└── Form/Type/         ← the gateway configuration form
```

Wire the SDK's services the same way as in [Symfony](symfony.md#wiring). Bind the interfaces,
share one [`SerializerProvider`](../../src/Serializer/SerializerProvider.php) and register the services you use.

Credentials per channel
-----------------------

A Sylius store with two channels often has two Polkurier accounts. The usual Sylius approach is a
**shipping gateway config** instead of container parameters: a configuration array attached to a
shipping method and edited in the admin.

```php
<?php

declare(strict_types=1);

namespace App\Polkurier\Shipping;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Sylius\Component\Core\Model\ShippingMethodInterface;

final readonly class GatewayConfigFactory
{
    public function __construct(private string $platformVersion) {}

    public function forMethod(ShippingMethodInterface $method): ConfigInterface
    {
        $config = $method->getConfiguration();

        return new Config(
            authorization: new AuthorizationMessage(
                token: (string)($config['polkurier_token'] ?? ''),
                login: (string)($config['polkurier_login'] ?? ''),
            ),
            platform: 'sylius',
            platformVersion: $this->platformVersion,
            sandbox: (bool)($config['polkurier_sandbox'] ?? true),
        );
    }
}
```

If the credentials belong to the channel and not to the shipping method, implement
[`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php) on top of [`Sylius\Component\Channel\Context\ChannelContextInterface`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Channel/Context/ChannelContextInterface.php):

```php
<?php

declare(strict_types=1);

namespace App\Polkurier;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;

final readonly class ChannelConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private ChannelContextInterface $channelContext,
        private CredentialStore $credentials,
        private ConfigInterface $fallback,
    ) {}

    public function getConfig(): ConfigInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return $this->fallback;   // CLI, message handlers, fixtures
        }

        return new Config(
            authorization: new AuthorizationMessage(...$this->credentials->for($channel)),
            platform: 'sylius',
            platformVersion: '1.0.0',
        );
    }
}
```

You need the [`Sylius\Component\Channel\Context\ChannelNotFoundException`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Channel/Context/ChannelNotFoundException.php) branch. [`Sylius\Component\Channel\Context\ChannelContextInterface`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Channel/Context/ChannelContextInterface.php) throws outside a
storefront request (in Messenger handlers, commands and fixtures). If the provider doesn't catch it,
all of those break.

> As in any Symfony application, a service keeps the configuration it was built with. Under
> Messenger workers, a channel-scoped provider will **not** make a long-lived service switch
> channels. The fix is the same as in
> [Symfony: per-tenant credentials](symfony.md#per-tenant-credentials). Build the client per
> message and share the serializer.

The shipping method
-------------------

Sylius treats *calculating* a shipping cost and *exporting* a shipment as separate steps. You'll
use the SDK almost only for the export.

**Calculators** run on every cart change. Use the built-in ones (flat rate, per unit rate, a
channel price matrix) and don't call [`OrdersServiceInterface::orderValuation()`](../../src/Api/Service/OrdersServiceInterface.php) from them. A request to a third party on
every cart recalculation makes the storefront feel broken.

If you really need live prices, write a calculator that reads from a cache filled by your own
worker:

```php
<?php

declare(strict_types=1);

namespace App\Polkurier\Shipping;

use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;

final readonly class CachedPolkurierCalculator implements CalculatorInterface
{
    public function __construct(private QuoteCache $quotes) {}

    public function calculate(ShipmentInterface $subject, array $configuration): int
    {
        // Falls back to the configured flat rate when nothing is cached. Never calls the API.
        return $this->quotes->find($subject, $configuration) ?? (int)$configuration['fallback_amount'];
    }

    public function getType(): string
    {
        return 'polkurier_cached';
    }
}
```

```xml
<service id="app.shipping_calculator.polkurier" class="App\Polkurier\Shipping\CachedPolkurierCalculator">
    <argument type="service" id="App\Polkurier\Shipping\QuoteCache"/>
    <tag name="sylius.shipping_calculator" calculator="polkurier_cached" label="Polkurier"
         form-type="App\Polkurier\Form\Type\PolkurierCalculatorConfigurationType"/>
</service>
```

Exporting a shipment
--------------------

Hook into the shipment state machine. Sylius defines `sylius_shipment` with a `ship` transition.
Add a callback on it that dispatches a Messenger message, and place the Polkurier order in the
message handler.

`config/packages/winzou_state_machine.yaml`:

```yaml
winzou_state_machine:
    sylius_shipment:
        callbacks:
            after:
                app_polkurier_export:
                    on: ['ship']
                    do: ['@App\Polkurier\Shipping\ShipmentExporter', 'export']
                    args: ['object']
```

```php
<?php

declare(strict_types=1);

namespace App\Polkurier\Shipping;

use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class ShipmentExporter
{
    public function __construct(private MessageBusInterface $bus) {}

    public function export(ShipmentInterface $shipment): void
    {
        if ($shipment->getTracking() !== null) {
            return;   // Already exported.
        }

        $this->bus->dispatch(new ExportShipment((int)$shipment->getId()));
    }
}
```

The handler places the order and saves the Polkurier order number as the tracking code, so Sylius
shows it wherever it displays tracking:

```php
<?php

declare(strict_types=1);

namespace App\Polkurier\Shipping;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final readonly class ExportShipmentHandler
{
    public function __construct(
        private OrdersServiceInterface $orders,
        private ShipmentRepositoryInterface $shipments,
        private ShipmentMapper $mapper,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(ExportShipment $message): void
    {
        $shipment = $this->shipments->find($message->shipmentId);

        if ($shipment === null || $shipment->getTracking() !== null) {
            return;
        }

        try {
            $created = $this->orders->createOrder($this->mapper->toMessage($shipment));
        } catch (ValidationException | WebserviceException $e) {
            throw new UnrecoverableMessageHandlingException($e->getMessage(), previous: $e);
        } catch (ConnectionException $e) {
            throw $e;   // Retry. The tracking guard above makes that safe.
        }

        $shipment->setTracking($created->getOrderNumber());
        $this->entityManager->flush();
    }
}
```

Don't leave out [`Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Messenger/Exception/UnrecoverableMessageHandlingException.php) for the permanent failures. Without it, an
address Polkurier will never accept uses up every retry and ends up in the failure transport, where
it looks like an outage.

Mapping a Sylius shipment
-------------------------

```php
<?php

declare(strict_types=1);

namespace App\Polkurier\Shipping;

use Polkurier\Webservice\Builder\Order\CreateOrderMessageBuilder;
use Polkurier\Webservice\Builder\Order\OrderAddressMessageBuilder;
use Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Sylius\Component\Core\Model\ShipmentInterface;

final readonly class ShipmentMapper
{
    public function __construct(private WarehouseAddressFactory $warehouse) {}

    public function toMessage(ShipmentInterface $shipment): CreateOrderMessage
    {
        $order   = $shipment->getOrder();
        $address = $order->getShippingAddress();

        $recipient = OrderAddressMessageBuilder::create()
            ->person(trim($address->getFirstName() . ' ' . $address->getLastName()))
            ->street($address->getStreet())
            ->houseNumber('1')     // Sylius has no separate house number; see below.
            ->city($address->getCity())
            ->postcode($address->getPostcode())
            ->phone((string)$address->getPhoneNumber())
            ->email((string)$order->getCustomer()?->getEmail());

        if ($address->getCompany()) {
            $recipient->company($address->getCompany());
        }

        return CreateOrderMessageBuilder::create()
            ->courier($this->carrierCodeFor($shipment))
            ->shipmentType(ShipmentTypeEnum::Box)
            ->packs([$this->pack($shipment)])
            ->sender($this->warehouse->forChannel($order->getChannel()))
            ->recipient($recipient->build())
            ->description($order->getNumber())
            ->build();
    }

    private function pack(ShipmentInterface $shipment): PackMessage
    {
        $weight = 0.0;

        foreach ($shipment->getUnits() as $unit) {
            $weight += (float)($unit->getShippable()?->getShippingWeight() ?? 0);
        }

        return PackMessageBuilder::create()
            ->length(30)->width(20)->height(15)
            ->weight(max($weight, 0.1))     // The API requires a positive weight.
            ->type(PackTypeEnum::Standard)
            ->build();
    }
}
```

There are two problems specific to Sylius.

**Sylius's [`Sylius\Component\Addressing\Model\AddressInterface`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Addressing/Model/AddressInterface.php) has no house number.**
[`AddressInterface::getStreet()`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Addressing/Model/AddressInterface.php) returns one free-text line.
Most carriers need the number as a separate field, and the API validates it. You can extend
[`Sylius\Component\Core\Model\Address`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Core/Model/Address.php) with a `houseNumber` field and add it to the checkout form, or parse the number out of the
street and accept some failures. Parsing Polish street lines isn't reliable, so extend the entity.

**[`AddressInterface::getPhoneNumber()`](https://github.com/Sylius/Sylius/blob/2.0/src/Sylius/Component/Addressing/Model/AddressInterface.php) is optional in Sylius, but carriers need it.** Make it required in the
shipping address form for the countries you ship to. Otherwise a [`ValidationException`](../../src/Exception/ValidationException.php) on `phone`
will be by far your most common failure.

Pickup points at checkout
-------------------------

Add a field to the shipping step of the checkout, filled from a cached list or the map widget:

```php
// In a form extension over Sylius\Bundle\CoreBundle\Form\Type\Checkout\SelectShippingType
$builder->add('pickupPoint', ChoiceType::class, [
    'choices' => $this->pickupPoints->forPostcode($address->getPostcode()),
    'required' => false,
]);
```

Save the selected ID on the shipment (in a custom field or a small related entity). In the mapper,
read it back and pass it to [`OrderAddressMessageBuilder::pointId()`](../../src/Builder/Order/OrderAddressMessageBuilder.php).

Cache the lists. See [Recipes: caching](../recipes.md#caching-what-does-not-change).

Labels in the admin
-------------------

The Sylius admin is built with Symfony and Twig, so you need a route and a controller:

```php
#[Route('/admin/polkurier/labels', name: 'app_admin_polkurier_labels', methods: ['POST'])]
public function labels(Request $request): Response
{
    $numbers = $request->request->all('shipments');

    $document = $this->orders->getLabel(new GetDocumentMessage(orderNo: $numbers));

    return new Response($document->getDecodedFile(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="labels.pdf"',
    ]);
}
```

Add it to the shipment index grid as a bulk action by overriding the grid template. [`OrdersServiceInterface::getLabel()`](../../src/Api/Service/OrdersServiceInterface.php)
accepts a list, so 50 selected shipments take one API call and produce one PDF.

Testing
-------

The Sylius Behat suite runs against the test container, so you can use the transport override from
[Symfony](symfony.md#testing):

```yaml
# config/services_test.yaml
services:
    Polkurier\Webservice\Http\ClientFactoryInterface: '@Polkurier\Webservice\Testing\RecordingClient'
```

A Behat scenario can then check what the SDK *sent*, such as the carrier code or the postcode, in
addition to what your code did. See [Testing](../testing.md).
