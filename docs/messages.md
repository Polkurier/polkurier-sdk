Messages and models
===================

Everything sent to or received from the API lives in one of two namespaces:

```
Request/    65 classes: everything you send
Response/   81 classes: everything you get back
```

Both sit at the root of `Polkurier\Webservice`, not under a shared `Model` namespace. They hold
payload classes only. The validator, the serializer and the HTTP envelope live elsewhere.

Requests
--------

A request payload (a **message**) is a readonly class that implements `Api\MessageInterface`. You
construct it with named arguments:

```php
use Polkurier\Webservice\Request\Courier\GetPointMessage;

$search = new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
);
```

Properties are private and have getters. Because the class is readonly, a message can't change
between being validated and being serialized.

### Three ways to build one

**1. The constructor, with named arguments.** This is the default and works best when you have
every value up front. You can skip optional parameters in any order.

**2. A [builder](builders.md).** The 22 larger messages have one. Use it when the payload is put
together over several steps or many of its fields are conditional:

```php
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
}

$points = $client->courier()->findCourierPoints($search->build());
```

**3. Spreading an array into named arguments.** Useful when your input is already an associative
array, e.g. a form submission or a queue payload:

```php
$message = new GetPointMessage(...$validatedInput);
```

PHP matches string keys to parameter names, and a key with no matching parameter throws an
`Error` instead of being ignored. That's fine for input you have already validated. Don't do it
with raw input. Messages have no `fromArray()`, and the serializer only maps responses into
objects, never requests.

### Extending a message

Messages are not `final`. If the API has a field the SDK doesn't support yet, you can add it in a
subclass. It will serialize correctly as long as the new property has its own
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

Class-level validation rules are inherited, so the parent's cross-field rules still apply. Treat
this as a stopgap and report the missing field so it can be added to the SDK.

Responses
---------

Response classes are built through their constructors. If a required field is missing from the
reply, mapping fails straight away and the error names the field. You never get an object whose
getters throw, so a method that returns an `Order` always returns a complete one.

Most response classes are readonly. These 13 are not: `Order`, `Address`, `BankAccount`, `Pack`,
`PackTemplate`, `Cod`, `Cod\ReturnDetails`, `Pickup`, `Waybill`, `Item`, `IndividualPricing`,
`BaseAddress` and `BasePack`. Some of them have setters. They are the entity-like classes an
application often keeps and edits locally before saving. The SDK itself never calls those setters.

### Lists, maps and pages

Responses come in four shapes:

| Shape | Example | What you get |
|-------|---------|--------------|
| One object | `createOrder()` | `CreatedOrderMessage` |
| A list | `availableCarriers()` | `Carrier[]`, possibly empty |
| A map of strings | `getCountries()` | `array<string, string>` |
| A page | `getOrders()` | `OrderListMessage` wrapping `Order[]` |

A page has its own navigation getters:

```php
$list->getResult();       // Order[]
$list->getTotalRows();
$list->getTotalPages();
$list->getCurrentPage();
$list->getPageSize();
```

Five endpoints return pages, and they do **not** all have the same fields. Check this table before
you write a loop around `getTotalPages()`:

| Class | `getResult()` | Rows | Pages | Current page | Page size |
|-------|---------------|------|-------|--------------|-----------|
| `Order\OrderListMessage` | `Order[]` | yes | **yes** | yes | yes |
| `MoneyBox\MoneyBoxPayoutListMessage` | `MoneyBoxPayoutMessage[]` | yes | **yes** | yes | yes |
| `Invoice\InvoiceListMessage` | `InvoiceMessage[]` | yes | no | yes | yes |
| `Ticket\TicketListMessage` | `TicketMessage[]` | yes | no | yes | yes |
| `Payment\PrepaidHistoryMessage` | `PrepaidEntryMessage[]` | yes | no | yes | yes |

For the three without a page count, work it out from the row count and page size, or stop when a
page comes back short. [Recipes: paging](recipes.md#paging-through-a-list) shows both loops.

### The two unmapped responses

`orders()->orderValuation()` and `tickets()->getContactForm()` return `array<array-key, mixed>`,
which is just the decoded JSON. Their shape depends on the request, so there's no fixed structure
to map. Every other method returns a typed object.

Why requests and responses are split
------------------------------------

Request and response classes stay separate even when they describe the same thing, because the
shapes differ.

`Request\Order\PackMessage` has the dimensions you choose. `Response\Order\Pack` has those plus
what the carrier charged for them. `Request\Order\CodMessage` says what to collect.
`Response\Order\Cod` says what happened to the money: when it was paid out, whether it was
returned, and why.

The pairs:

| You send | You receive |
|----------|-------------|
| `Request\Address\SaveMessage` | `Response\Address` |
| `Request\BankAccount\SaveMessage` | `Response\BankAccount` |
| `Request\PackTemplate\SaveMessage` | `Response\PackTemplate` |
| `Request\Order\PackMessage` | `Response\Order\Pack` |
| `Request\Order\CodMessage` | `Response\Order\Cod` |
| `Request\Order\PickupMessage` | `Response\Order\Pickup` |
| `Request\Order\CreateOrderMessage` | `Response\Order` |

The docblock of each response class names the request that writes it, so you can find the pair
from either side.

The main benefit is nullability. A response field can be non-nullable when the API always sends
it. With a single shared class, any field that is optional on one side would have to be optional
on both, and most getters would return a nullable type.

Naming
------

Under `Request`, every class ends in `Message`: `CreateOrderMessage`, `SaveMessage`,
`GetOrdersMessage`. The suffix means "a payload".

Under `Response`, it depends. Entity-like classes that exist independently of any call are named
after the thing itself: `Order`, `Address`, `Carrier`, `CourierPoint`, `Country`, `BankAccount`,
`PackTemplate`. Classes that only exist as the answer from one endpoint keep the suffix:
`OrderListMessage`, `AccountInfoMessage`, `HeartbeatMessage`.

When a request and a response class share a short name, like `Request\Order\PickupMessage` and
`Response\Order\Pickup`, import one and use the fully qualified name for the other, or alias it:

```php
use Polkurier\Webservice\Request\Order\PickupMessage;
use Polkurier\Webservice\Response\Order\Pickup as PickupResult;
```

Wire names
----------

No property is named after the API field it maps to. Each one has a `#[SerializedName]` instead:

```php
#[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
private ShipmentTypeEnum $shipmentType,
```

Names like `codbankaccount`, `is_cover_address`, `shipmenttype` and `value_nett` never show up in
your code. This is a firm rule in the SDK. The wire names are inconsistent, and keeping them in an
attribute means they can change without a breaking release. [Serialization](serialization.md)
explains how it works.

Each wire name is also a class constant:

```php
CreateOrderMessage::FIELD_SHIPMENT_TYPE;   // 'shipmenttype'
```

The attribute references the constant, so each wire name is written in one place only. Use the
constants when you need the API's own field names, e.g. when reading a `ValidationException`,
matching a field in an error from the API, or building a raw request for debugging.

Enums
-----

Values the API accepts and returns are backed enums under `Polkurier\Webservice\Enum`, grouped by
area. The ones you're most likely to use:

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

There are also four protocol-level enums under `Enum\Http`: `ApiMethodEnum` (every endpoint, see
the [API method index](api-methods.md)), `ApiStatusCodeEnum`, `ApiErrorCodeEnum` and
`ResponseStatusEnum`.

### Enums on responses, strings on some fields

When the set of values is closed and known, the property is typed as the enum and the serializer
maps it in both directions. When it isn't (a carrier code, an order status string), the property
is a string and the enum lists the known values next to it:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

$order->getStatusCode() === OrderStatusCodeEnum::Delivered->value
```

If the API adds a new value to a field typed as an enum, the whole response fails to map. On a
string field, the new value just doesn't match any enum case, and your code can handle that.

### Handling an unknown enum value

If the API sends a value that isn't in an enum the SDK uses as a property type, denormalization
fails and you get the error during mapping. This is intended, since it is a real incompatibility.
If you need to keep working until a fix is released, register a custom normalizer ahead of
`BackedEnumNormalizer`. See
[Serialization: replacing a normalizer](serialization.md#2-replace-or-add-a-normalizer).

No interfaces on models
-----------------------

Request and response classes have no interfaces, and won't get any. Wire names, formats and dates
are defined in serializer attributes on the classes themselves, so a different implementation of
an interface wouldn't serialize.

Type-hint the classes directly. The interfaces under `Api` are the ones where your own
implementation actually changes something.
