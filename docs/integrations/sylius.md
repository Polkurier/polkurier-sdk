Sylius
======

Sylius 1.13 / 2.x. Sylius is Symfony, so start with [the Symfony page](symfony.md) — everything
there applies unchanged. This page covers what is Sylius-specific: channel-scoped credentials, the
shipping method and gateway model, the state machine, and shipment export.

The plugin skeleton
-------------------

Sylius plugins are ordinary bundles. Everything below goes into one:

```
src/
├── PolkurierShippingPlugin.php
├── DependencyInjection/
├── Resources/config/services.xml
├── Shipping/          ← gateway, mapper
├── StateMachine/      ← callbacks
└── Form/Type/         ← the gateway configuration form
```

The SDK's own services are wired exactly as in [Symfony](symfony.md#wiring): bind the interfaces,
share one `SerializerProvider`, register the services you use. Nothing about Sylius changes that.

Credentials per channel
-----------------------

A Sylius store with two channels frequently has two Polkurier accounts. Sylius's own idiom for
this is a **shipping gateway config** — a JSON-ish configuration attached to a shipping method,
edited in the admin — rather than container parameters.

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

Where credentials are per channel rather than per method, a `ConfigProviderInterface` reading
`ChannelContextInterface` is the better fit:

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

The `ChannelNotFoundException` branch is not optional. `ChannelContextInterface` throws outside a
storefront request — in Messenger handlers, in commands, in fixtures — and a provider that lets
that escape breaks every one of them.

> As in every Symfony application, a service caches its configuration at construction. Under
> Messenger workers this means a channel-scoped provider does **not** make a long-lived service
> switch channels. See [Symfony — per-tenant credentials](symfony.md#per-tenant-credentials); the
> answer is the same: build the client per message, share the serializer.

The shipping method
-------------------

Sylius separates *calculating* a shipping cost from *exporting* a shipment, and the SDK belongs
almost entirely on the export side.

**Calculators** run on every cart change. Use Sylius's own — flat rate, per-unit rate, a channel
price matrix — and not `orderValuation()`. A third-party round trip inside cart recalculation is
the fastest way to make a storefront feel broken.

If you genuinely need live pricing, write a calculator that reads from a cache your own worker
fills:

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
        // Falls back to the configured flat rate when nothing is cached; never calls the API.
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

The natural hook is the shipment state machine. Sylius ships `sylius_shipment` with a `ship`
transition; place the Polkurier order as a callback on it, via a Messenger message rather than
inline.

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

The handler does the real work, and stores the Polkurier order number as the tracking code — which
gets you Sylius's tracking display for free:

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
            throw $e;   // Retry; the tracking guard above makes that safe.
        }

        $shipment->setTracking($created->getOrderNumber());
        $this->entityManager->flush();
    }
}
```

`UnrecoverableMessageHandlingException` on the permanent failures is the important line. Without
it, an address Polkurier will never accept burns every retry and lands in the failure transport
looking like an outage.

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

Two Sylius-specific frictions.

**Sylius's `AddressInterface` has no house number.** `getStreet()` is one free-text line. Most
carriers need the number separately, and the API validates for it. Either extend `Address` with a
`houseNumber` field and add it to the checkout form — the clean answer — or parse it out of the
street and accept the failure rate. Parsing Polish street lines is not reliable; extend the entity.

**`getPhoneNumber()` is optional in Sylius and effectively required by carriers.** Make it required
in the shipping address form for the countries you serve, or expect `ValidationException` on
`phone` to be your most common failure by a wide margin.

Pickup points at checkout
-------------------------

Add a field to the checkout's shipping step, populated from a cached listing or the map widget:

```php
// In a form extension over Sylius\Bundle\CoreBundle\Form\Type\Checkout\SelectShippingType
$builder->add('pickupPoint', ChoiceType::class, [
    'choices' => $this->pickupPoints->forPostcode($address->getPostcode()),
    'required' => false,
]);
```

Store the chosen id on the shipment (a custom field or a small associated entity) and read it back
in the mapper with `OrderAddressMessageBuilder::pointId()`.

Cache the listings. See [Recipes — caching](../recipes.md#caching-what-does-not-change).

Labels in the admin
-------------------

Sylius's admin is Symfony plus Twig, so a route and a controller:

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

Hook it into the shipment index grid as a bulk action through a grid template override.
`getLabel()` takes a list, so selecting fifty shipments is one API call and one PDF.

Testing
-------

Sylius's Behat suite runs against the test container, so the transport override from
[Symfony](symfony.md#testing) applies:

```yaml
# config/services_test.yaml
services:
    Polkurier\Webservice\Http\ClientFactoryInterface: '@Polkurier\Webservice\Testing\RecordingClient'
```

That lets a Behat scenario assert on what the SDK *sent* — which carrier code, which postcode —
rather than only on what your code did. See [Testing](../testing.md).
