Messages and models
===================

Everything sent to or received from the API lives in one of two namespaces:

```
Request/    72 classes: everything you send
Response/   85 classes: everything you get back
```

Both sit at the root of [`Polkurier\Webservice`](../src/), not under a shared `Model` namespace. They hold
payload classes only. The validator, the serializer and the HTTP envelope live elsewhere.

Requests
--------

A request payload (a **message**) is a readonly class that implements [`Api\MessageInterface`](../src/Api/MessageInterface.php). You
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

**2. A [builder](builders.md).** The 23 larger messages have one. Use it when the payload is put
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
getters throw, so a method that returns an [`Order`](../src/Response/Order.php) always returns a complete one.

Response classes are readonly too, and have getters only. To change a value, build a new request
message, e.g. an [`Address\SaveMessage`](../src/Request/Address/SaveMessage.php) from an [`Address`](../src/Response/Address.php) you read.

### Lists, maps and pages

Responses come in four shapes:

| Shape | Example | What you get |
|-------|---------|--------------|
| One object | [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) | [`CreatedOrderMessage`](../src/Response/Order/CreatedOrderMessage.php) |
| A list | [`CourierServiceInterface::availableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | `Carrier[]`, possibly empty |
| A map of strings | [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) | `array<string, string>` |
| A page | [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderListMessage`](../src/Response/Order/OrderListMessage.php) wrapping `Order[]` |

A page has its own navigation getters:

```php
$list->getResult();       // Order[]
$list->getTotalRows();
$list->getTotalPages();
$list->getCurrentPage();
$list->getPageSize();
```

Five endpoints return pages, and they do **not** all have the same fields. Check this table before
you write a paging loop:

| Class | `getResult()` | Rows | Pages | Current page | Page size |
|-------|---------------|------|-------|--------------|-----------|
| [`Order\OrderListMessage`](../src/Response/Order/OrderListMessage.php) | `Order[]` | yes | **yes** | yes | yes |
| [`MoneyBox\MoneyBoxPayoutListMessage`](../src/Response/MoneyBox/MoneyBoxPayoutListMessage.php) | `MoneyBoxPayoutMessage[]` | yes | **yes** | yes | yes |
| [`Invoice\InvoiceListMessage`](../src/Response/Invoice/InvoiceListMessage.php) | `InvoiceMessage[]` | yes | no | yes | yes |
| [`Ticket\TicketListMessage`](../src/Response/Ticket/TicketListMessage.php) | `TicketMessage[]` | yes | no | yes | yes |
| [`Payment\PrepaidHistoryMessage`](../src/Response/Payment/PrepaidHistoryMessage.php) | `PrepaidEntryMessage[]` | yes | no | yes | yes |

For the three without a page count, work it out from the row count and page size, or stop when a
page comes back short. [Recipes: paging](recipes.md#paging-through-a-list) shows both loops.

A paging field the API sends as null reads as `0`.

Filtered down to one record, `get_orders` (by order number) and `get_tickets` (by id) answer with
that record alone. The SDK reads it as a page with one row, so
[`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) and [`TicketsServiceInterface::getTickets()`](../src/Api/Service/TicketsServiceInterface.php) return the same shape either way.

### The two unmapped responses

[`$client->orders()->orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) and [`$client->tickets()->getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) return `array<array-key, mixed>`,
which is just the decoded JSON. Their shape depends on the request, so there's no fixed structure
to map. Every other method returns a typed object.

Why requests and responses are split
------------------------------------

Request and response classes stay separate even when they describe the same thing, because the
shapes differ.

[`Request\Order\PackMessage`](../src/Request/Order/PackMessage.php) has the dimensions you choose. [`Response\Order\Pack`](../src/Response/Order/Pack.php) has those plus
what the carrier charged for them. [`Request\Order\CodMessage`](../src/Request/Order/CodMessage.php) says what to collect.
[`Response\Order\Cod`](../src/Response/Order/Cod.php) says what happened to the money: when it was paid out, whether it was
returned, and why.

The pairs:

| You send | You receive |
|----------|-------------|
| [`Request\Address\SaveMessage`](../src/Request/Address/SaveMessage.php) | [`Response\Address`](../src/Response/Address.php) |
| [`Request\BankAccount\SaveMessage`](../src/Request/BankAccount/SaveMessage.php) | [`Response\BankAccount`](../src/Response/BankAccount.php) |
| [`Request\PackTemplate\SaveMessage`](../src/Request/PackTemplate/SaveMessage.php) | [`Response\PackTemplate`](../src/Response/PackTemplate.php) |
| [`Request\Order\PackMessage`](../src/Request/Order/PackMessage.php) | [`Response\Order\Pack`](../src/Response/Order/Pack.php) |
| [`Request\Order\CodMessage`](../src/Request/Order/CodMessage.php) | [`Response\Order\Cod`](../src/Response/Order/Cod.php) |
| [`Request\Order\PickupMessage`](../src/Request/Order/PickupMessage.php) | [`Response\Order\Pickup`](../src/Response/Order/Pickup.php) |
| [`Request\Order\CreateOrderMessage`](../src/Request/Order/CreateOrderMessage.php) | [`Response\Order`](../src/Response/Order.php) |

The docblock of each response class names the request that writes it, so you can find the pair
from either side.

The main benefit is nullability. A response field can be non-nullable when the API always sends
it. With a single shared class, any field that is optional on one side would have to be optional
on both, and most getters would return a nullable type.

Naming
------

Under `Request`, every class ends in `Message`: [`CreateOrderMessage`](../src/Request/Order/CreateOrderMessage.php), [`Address\SaveMessage`](../src/Request/Address/SaveMessage.php),
[`GetOrdersMessage`](../src/Request/Order/GetOrdersMessage.php). The suffix means "a payload".

Under `Response`, it depends. Entity-like classes that exist independently of any call are named
after the thing itself: [`Order`](../src/Response/Order.php), [`Address`](../src/Response/Address.php), [`Carrier`](../src/Response/Carrier.php), [`CourierPoint`](../src/Response/CourierPoint.php), [`Country`](../src/Response/Country.php), [`BankAccount`](../src/Response/BankAccount.php),
[`PackTemplate`](../src/Response/PackTemplate.php). Classes that only exist as the answer from one endpoint keep the suffix:
[`OrderListMessage`](../src/Response/Order/OrderListMessage.php), [`AccountInfoMessage`](../src/Response/Account/AccountInfoMessage.php), [`HeartbeatMessage`](../src/Response/Util/HeartbeatMessage.php).

When a request and a response class share a short name, like [`Request\Order\PickupMessage`](../src/Request/Order/PickupMessage.php) and
[`Response\Order\Pickup`](../src/Response/Order/Pickup.php), import one and use the fully qualified name for the other, or alias it:

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
constants when you need the API's own field names, e.g. when reading a [`ValidationException`](../src/Exception/ValidationException.php),
matching a field in an error from the API, or building a raw request for debugging.

Enums
-----

Values the API accepts and returns are backed enums under [`Polkurier\Webservice\Enum`](../src/Enum/), grouped by
area. The ones you're most likely to use:

| Enum | Used for |
|------|----------|
| [`Order\ShipmentTypeEnum`](../src/Enum/Order/ShipmentTypeEnum.php) | Box, envelope, pallet |
| [`Order\PackTypeEnum`](../src/Enum/Order/PackTypeEnum.php) | What kind of parcel |
| [`Order\AdditionalServiceEnum`](../src/Enum/Order/AdditionalServiceEnum.php) | Services to buy with the shipment (25 of them) |
| [`Order\OrderStatusCodeEnum`](../src/Enum/Order/OrderStatusCodeEnum.php) | Delivery state |
| [`Order\CodTypeEnum`](../src/Enum/Order/CodTypeEnum.php), [`CodStatusEnum`](../src/Enum/Order/CodStatusEnum.php), [`CodReturnTypeEnum`](../src/Enum/Order/CodReturnTypeEnum.php), [`CodReturnReasonEnum`](../src/Enum/Order/CodReturnReasonEnum.php) | Cash on delivery |
| [`Order\LabelFileFormatEnum`](../src/Enum/Order/LabelFileFormatEnum.php) | Printer language for labels |
| [`Order\OrderItemTypeEnum`](../src/Enum/Order/OrderItemTypeEnum.php) | Line item classification |
| [`Account\PayerAddressTypeEnum`](../src/Enum/Account/PayerAddressTypeEnum.php) | Billing, correspondence or contact |
| [`Account\PayerUserTypeEnum`](../src/Enum/Account/PayerUserTypeEnum.php), [`RegulationsTypeEnum`](../src/Enum/Account/RegulationsTypeEnum.php) | Account and terms |
| [`Address\AddressTypeEnum`](../src/Enum/Address/AddressTypeEnum.php) | Address book entry kind |
| [`Courier\PointFunctionEnum`](../src/Enum/Courier/PointFunctionEnum.php), [`PointRequirementEnum`](../src/Enum/Courier/PointRequirementEnum.php) | What a pickup point can do |
| [`Courier\CourierMessageTypeEnum`](../src/Enum/Courier/CourierMessageTypeEnum.php), [`CourierMessageSeverityEnum`](../src/Enum/Courier/CourierMessageSeverityEnum.php) | Notice board |
| [`Courier\AdditionalFieldTypeEnum`](../src/Enum/Courier/AdditionalFieldTypeEnum.php) | Carrier-specific extra fields |
| [`Payment\PaymentModeEnum`](../src/Enum/Payment/PaymentModeEnum.php) | What a payment settles |
| [`MoneyBox\MoneyBoxPayoutTypeEnum`](../src/Enum/MoneyBox/MoneyBoxPayoutTypeEnum.php), [`MoneyBoxFeePaymentTypeEnum`](../src/Enum/MoneyBox/MoneyBoxFeePaymentTypeEnum.php) | Payouts |
| [`Invoice\InvoiceTypeEnum`](../src/Enum/Invoice/InvoiceTypeEnum.php) | Invoice kind |
| [`Ticket\TicketStatusEnum`](../src/Enum/Ticket/TicketStatusEnum.php), [`TicketAuthorEnum`](../src/Enum/Ticket/TicketAuthorEnum.php), [`TicketOriginEnum`](../src/Enum/Ticket/TicketOriginEnum.php), [`TicketTimelineItemTypeEnum`](../src/Enum/Ticket/TicketTimelineItemTypeEnum.php) | Support |
| [`KnowledgeBase\KnowledgeBaseEntryTypeEnum`](../src/Enum/KnowledgeBase/KnowledgeBaseEntryTypeEnum.php) | Category or article |
| [`TwoFactorToken\TwoFactorNamespaceEnum`](../src/Enum/TwoFactorToken/TwoFactorNamespaceEnum.php) | Which operation a code authorises |
| [`TwoFactorToken\TwoFactorTransportTypeEnum`](../src/Enum/TwoFactorToken/TwoFactorTransportTypeEnum.php) | How a code reaches the customer |

In a response, a value the SDK doesn't know reads as `null`, so every enum field in a response is
nullable. In a list of enum values, unknown values are left out. A value added to the API after
your SDK version was released then doesn't make the whole response unreadable. Requests stay strict:
you can only send the cases the enum has.

Nothing is lost, though. After a call, [`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php) lists each value
the SDK couldn't read, with its enum and where in the response it was, and
[`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php) returns the HTTP response exactly as the API sent it:

```php
$templates = $client->packTemplates()->getPackTemplates();

foreach ($client->getLastUnknownEnumValues() as $unknown) {
    // e.g. Polkurier\Webservice\Enum\Order\PackTypeEnum, "XX", "[0].type"
    echo $unknown->getEnum(), ' ', $unknown->getValue(), ' ', $unknown->getPath(), "\n";
}

$json = (string) $client->getLastResponse()?->getBody();
```

With logging on, each unknown value is also logged as a warning, so you know the SDK needs
updating. See [serialization](serialization.md#unknown-enum-values) for how it works.

There are also three protocol-level enums under [`Enum\Http`](../src/Enum/Http/): [`ApiMethodEnum`](../src/Enum/Http/ApiMethodEnum.php) (every endpoint, see
the [API method index](api-methods.md)), [`ApiErrorCodeEnum`](../src/Enum/Http/ApiErrorCodeEnum.php) and
[`ResponseStatusEnum`](../src/Enum/Http/ResponseStatusEnum.php).

### Enums on responses, strings on some fields

When the set of values is closed and known, the property is typed as the enum and the serializer
maps it in both directions. When it isn't (a carrier code, an order status string), the property
is a string and the enum lists the known values next to it:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

$order->getStatusCode() === OrderStatusCodeEnum::Delivered->value
```

On a string field, a new value just doesn't match any enum case. On an enum field, it reads as
`null`, as described above. Either way the response still maps, and your code decides what to do
with a value it doesn't recognise.

No interfaces on models
-----------------------

Request and response classes have no interfaces, and won't get any. Wire names, formats and dates
are defined in serializer attributes on the classes themselves, so a different implementation of
an interface wouldn't serialize.

Type-hint the classes directly. The interfaces under `Api` are the ones where your own
implementation actually changes something.
