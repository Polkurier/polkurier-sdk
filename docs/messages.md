Messages and models
===================

Everything that crosses the wire lives in one of two namespaces, split by the direction it travels:

```
Request/    65 classes — everything you send
Response/   81 classes — everything you get back
```

They sit at the root of `Polkurier\Webservice` rather than under a shared `Model`, because the
direction is the distinction worth having in the namespace. Nothing else belongs in either one: not
the validator, not the serializer, not the HTTP envelope.

Requests
--------

A request payload — a **message** — is a readonly class implementing `Api\MessageInterface`,
constructed with named arguments:

```php
use Polkurier\Webservice\Request\Courier\GetPointMessage;

$search = new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
);
```

Properties are private with getters. Readonly is not decoration: a message is validated and then
serialized, and there is no window between those two in which it could change.

### Three ways to build one

**1. The constructor, with named arguments.** The default, and the clearest when every value is to
hand. Optional parameters can be skipped in any order.

**2. A [builder](builders.md).** For the twenty-two larger messages, when the payload is assembled
across several steps or half its fields are conditional:

```php
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
}

$points = $client->courier()->findCourierPoints($search->build());
```

**3. Named-argument spreading**, when your input is already an associative array — a form
submission, a queue payload:

```php
$message = new GetPointMessage(...$validatedInput);
```

PHP matches string keys to parameter names, so a key that does not correspond to a parameter is an
`Error` rather than a silently ignored field. That makes it safe enough for input you have already
validated, and a poor idea for input you have not. There is no `fromArray()` on messages, and no
deserialization path into them — the serializer maps responses into objects, not requests.

### Extending a message

Messages are not `final`. A subclass adding a field the API has grown but the SDK has not yet
caught up with will serialize correctly, provided the new property carries its own
`#[SerializedName]`:

```php
use Symfony\Component\Serializer\Attribute as Serializer;

class ExtendedOrderMessage extends CreateOrderMessage
{
    public function __construct(
        // … the parent's arguments …
        #[Serializer\SerializedName('some_new_field')]
        private readonly ?string $someNewField = null,
    ) {
        parent::__construct(/* … */);
    }
}
```

Class-level validation rules are inherited, so the parent's cross-field rules still apply. This is
an escape hatch, not a migration path: report the missing field so it can be added properly.

Responses
---------

Response classes are built through their constructors. A response either arrives whole or fails
immediately, naming the field that was missing. There is no third state where an object exists but
a getter on it raises — which matters, because a method promising an `Order` is only as good as
that guarantee.

Most are readonly. Twelve are not — `Order`, `Address`, `BankAccount`, `Pack`, `PackTemplate`,
`Cod`, `Pickup`, `Waybill`, `Item`, `IndividualPricing`, `BaseAddress`, `BasePack` — and some of
those carry setters. They are the entity-shaped ones, the things an application tends to hold onto
and adjust locally before storing. Nothing in the SDK reads a setter; they exist for you.

### Lists, maps and pages

Four shapes come back, and knowing which is which saves a lot of guessing:

| Shape | Example | What you get |
|-------|---------|--------------|
| One object | `createOrder()` | `CreatedOrderMessage` |
| A list | `availableCarriers()` | `Carrier[]`, possibly empty |
| A map of strings | `getCountries()` | `array<string, string>` |
| A page | `getOrders()` | `OrderListMessage` wrapping `Order[]` |

A page carries its own navigation:

```php
$list->getResult();       // Order[]
$list->getTotalRows();
$list->getTotalPages();
$list->getCurrentPage();
$list->getPageSize();
```

Five endpoints page, and they do **not** all carry the same fields — worth knowing before you
write a loop against `getTotalPages()`:

| Class | `getResult()` | Rows | Pages | Current page | Page size |
|-------|---------------|------|-------|--------------|-----------|
| `Order\OrderListMessage` | `Order[]` | yes | **yes** | yes | yes |
| `MoneyBox\MoneyBoxPayoutListMessage` | `MoneyBoxPayoutMessage[]` | yes | **yes** | yes | yes |
| `Invoice\InvoiceListMessage` | `InvoiceMessage[]` | yes | no | yes | yes |
| `Ticket\TicketListMessage` | `TicketMessage[]` | yes | no | yes | yes |
| `Payment\PrepaidHistoryMessage` | `PrepaidEntryMessage[]` | yes | no | yes | yes |

For the three without a page count, derive it from the row count and the page size, or stop when a
page comes back short. [Recipes — paging](recipes.md#paging-through-a-list) has both loops.

### The two unmapped responses

`orders()->orderValuation()` and `tickets()->getContactForm()` return
`array<array-key, mixed>` — decoded JSON and nothing more. Their shape varies with what was asked
for, and there is no stable structure to map onto. Everything else in the SDK is a typed object;
these two are honest about not being one.

Why requests and responses are split
------------------------------------

The two halves are kept apart even where they describe the same thing, because they are not the
same shape.

`Request\Order\PackMessage` carries the dimensions you choose. `Response\Order\Pack` carries those
*and* what the carrier charged for them. `Request\Order\CodMessage` says what to collect;
`Response\Order\Cod` says what became of the money — when it was paid out, whether it was returned,
and why.

The pairs, and where each one is written:

| You send | You receive |
|----------|-------------|
| `Request\Address\SaveMessage` | `Response\Address` |
| `Request\BankAccount\SaveMessage` | `Response\BankAccount` |
| `Request\PackTemplate\SaveMessage` | `Response\PackTemplate` |
| `Request\Order\PackMessage` | `Response\Order\Pack` |
| `Request\Order\CodMessage` | `Response\Order\Cod` |
| `Request\Order\PickupMessage` | `Response\Order\Pickup` |
| `Request\Order\CreateOrderMessage` | `Response\Order` |

Each response class's docblock names the request that writes it, so the pairing is discoverable
from either end.

The practical gain is nullability. A response field can be non-nullable when the API always sends
it, rather than nullable because the request had nothing to put there. Merged into one class,
every field that is optional on either side would be optional on both, and every getter would
return a maybe.

Naming
------

Two conventions collide here, and it is worth stating plainly.

Under `Request`, everything ends in `Message`: `CreateOrderMessage`, `SaveMessage`,
`GetOrdersMessage`. That suffix means "a payload".

Under `Response`, the suffix is inconsistent, and deliberately so. The entity-shaped
things — the ones that exist independently of any call — are named for themselves: `Order`,
`Address`, `Carrier`, `CourierPoint`, `Country`, `BankAccount`, `PackTemplate`. The ones that only
exist as one endpoint's answer keep the suffix: `OrderListMessage`, `AccountInfoMessage`,
`HeartbeatMessage`.

So `Order` is a thing and `OrderListMessage` is an answer. When both a request and a response class
share a short name — `Request\Order\PickupMessage` and `Response\Order\Pickup` — import one and
qualify the other, or alias:

```php
use Polkurier\Webservice\Request\Order\PickupMessage;
use Polkurier\Webservice\Response\Order\Pickup as PickupResult;
```

Wire names
----------

No property is named after the API's own field. Each one carries a `#[SerializedName]` instead:

```php
#[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
private ShipmentTypeEnum $shipmentType,
```

`codbankaccount`, `is_cover_address`, `shipmenttype`, `value_nett` never reach your code. This is a
hard requirement of the SDK, not a stylistic preference — the wire names are inconsistent enough
that inheriting them would be a permanent tax, and keeping them behind an attribute is what lets
them change without a breaking release. [Serialization](serialization.md) covers the mechanism.

The names are also declared as class constants:

```php
CreateOrderMessage::FIELD_SHIPMENT_TYPE;   // 'shipmenttype'
```

Those constants are what the attribute references, so there is exactly one place each wire name is
written. They are useful when you need to speak the API's language directly — reading a
`ValidationException`, matching a field in an error from the service, or building a raw request for
debugging.

Enums
-----

Values the API accepts and returns are backed enums under `Polkurier\Webservice\Enum`, grouped by
area. The ones you will actually touch:

| Enum | Used for |
|------|----------|
| `Order\ShipmentTypeEnum` | Box, envelope, pallet |
| `Order\PackTypeEnum` | What kind of parcel |
| `Order\AdditionalServiceEnum` | Services to buy with the shipment (25 of them) |
| `Order\OrderStatusCodeEnum` | Delivery state |
| `Order\CodTypeEnum`, `CodStatusEnum`, `CodReturnTypeEnum`, `CodReturnReasonEnum` | Cash on delivery |
| `Order\LabelFileFormatEnum` | Printer language for labels |
| `Order\OrderItemTypeEnum` | Line item classification |
| `Account\PayerAddressTypeEnum` | Billing, correspondence or contact |
| `Account\PayerUserTypeEnum`, `RegulationsTypeEnum` | Account and terms |
| `Address\AddressTypeEnum` | Address book entry kind |
| `Courier\PointFunctionEnum`, `PointRequirementEnum` | What a pickup point can do |
| `Courier\CourierMessageTypeEnum`, `CourierMessageSeverityEnum` | Notice board |
| `Courier\AdditionalFieldTypeEnum` | Carrier-specific extra fields |
| `Payment\PaymentModeEnum` | What a payment settles |
| `MoneyBox\MoneyBoxPayoutTypeEnum`, `MoneyBoxFeePaymentTypeEnum` | Payouts |
| `Invoice\InvoiceTypeEnum` | Invoice kind |
| `Ticket\TicketStatusEnum`, `TicketAuthorEnum`, `TicketOriginEnum`, `TicketTimelineItemTypeEnum` | Support |
| `KnowledgeBase\KnowledgeBaseEntryTypeEnum` | Category or article |
| `TwoFactorToken\TwoFactorNamespaceEnum` | Which operation a code authorises |

And four under `Enum\Http` that describe the protocol rather than the domain: `ApiMethodEnum`
(every endpoint — see the [API method index](api-methods.md)), `ApiStatusCodeEnum`,
`ApiErrorCodeEnum` and `ResponseStatusEnum`.

### Enums on responses, strings on some fields

Where the API's value set is closed and known, the property is typed as the enum and the serializer
maps it both ways. Where it is not — a carrier code, an order status string — the property stays a
string and the enum exists alongside it as a set of known values:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

$order->getStatusCode() === OrderStatusCodeEnum::Delivered->value
```

That is deliberate. A closed enum on a field the API can extend turns a new value into a hard
failure of the whole response; a string with an enum beside it turns it into a comparison that
does not match. The second is recoverable and the first is not.

### Handling an unknown enum value

If the API does send a value outside an enum the SDK types strictly, denormalization fails and you
get the error at the point of mapping. That is the intended behaviour — it is a real
incompatibility — but if you need to survive it while a fix is released, the escape hatch is a
custom normalizer registered ahead of `BackedEnumNormalizer`. See
[Serialization: replacing a normalizer](serialization.md#2-replace-or-add-a-normalizer).

No interfaces on models — on purpose
------------------------------------

There are no interfaces for request or response classes, and there will not be. Their wire names,
formats and dates live in serializer attributes on the classes themselves, so a substitute
implementation would not serialize. An interface there advertises a swap that cannot work.

Type-hint the classes. The interfaces that do exist — under `Api` — are the ones where supplying
your own implementation genuinely does something.
