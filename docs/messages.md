Messages and models
===================

Records Polkurier stores, such as addresses, bank accounts, pack templates and orders, are under
[`Entity`](../src/Entity/), with the class you send and the class you read side by side. Everything else you send
is under [`Dto\Request`](../src/Dto/Request/), and everything else you get back under [`Dto\Response`](../src/Dto/Response/). They hold payload classes only.

Requests
--------

A request payload (a **message**) is a readonly class that implements [`Api\MessageInterface`](../src/Api/MessageInterface.php). You
construct it with named arguments:

```php
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;

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

**2. A [builder](builders.md).** The 24 larger messages have one. It suits a payload put together
over several steps, or one with many conditional fields:

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
`Error` instead of being ignored, so this suits input that has already been checked. Messages have
no `fromArray()`, and the serializer only maps responses into objects, never requests.

### Extending a message

The classes of records Polkurier stores aren't `final`: addresses, bank accounts, phone numbers,
pack templates, and orders with their packs, cash on delivery, collection and addresses. Every other
request and response class is. If the API has a field the SDK doesn't support yet, you can add it
to one of the open classes in a subclass. It will serialize correctly as long as the new property
has its own `#[SerializedName]`:

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

Class-level validation rules are inherited, so the parent's cross-field rules still apply.

Responses
---------

Response classes are built through their constructors. If a required field is missing from the
reply, mapping fails straight away and the error names the field. You never get an object whose
getters throw, so a method that returns an [`Order`](../src/Entity/Order/Order.php) always returns a complete one.

Response classes are readonly too, and have getters only. To change a value, build a new request
message, e.g. an [`SaveAddressMessage`](../src/Entity/Address/SaveAddressMessage.php) from an [`Address`](../src/Entity/Address/Address.php) you read.

### Lists, maps and pages

Apart from a `bool` for calls that only acknowledge, responses come in four shapes:

| Shape | Example | What you get |
|-------|---------|--------------|
| One object | [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) | [`CreatedOrderMessage`](../src/Dto/Response/Order/CreatedOrderMessage.php) |
| A list | [`CourierServiceInterface::availableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | `Carrier[]`, possibly empty |
| A map of strings | [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) | `array<string, string>` |
| A page | [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php) wrapping `Order[]` |

A page has its own navigation getters:

```php
$list->getResult();       // Order[]
$list->getTotalRows();
$list->getTotalPages();
$list->getCurrentPage();
$list->getPageSize();
```

Five endpoints return pages, and they do **not** all have the same fields:

| Class | `getResult()` | Rows | Pages | Current page | Page size |
|-------|---------------|------|-------|--------------|-----------|
| [`Order\OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php) | `Order[]` | yes | **yes** | yes | yes |
| [`MoneyBox\MoneyBoxPayoutListMessage`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutListMessage.php) | `MoneyBoxPayoutMessage[]` | yes | **yes** | yes | yes |
| [`Invoice\InvoiceListMessage`](../src/Dto/Response/Invoice/InvoiceListMessage.php) | `InvoiceMessage[]` | yes | no | yes | yes |
| [`Ticket\TicketListMessage`](../src/Dto/Response/Ticket/TicketListMessage.php) | `TicketMessage[]` | yes | no | yes | yes |
| [`Payment\PrepaidHistoryMessage`](../src/Dto/Response/Payment/PrepaidHistoryMessage.php) | `PrepaidEntryMessage[]` | yes | no | yes | yes |

For the three without a page count, work it out from the row count and page size, or stop when a
page comes back short. [Recipes: paging](recipes.md#paging-through-a-list) shows both loops.

A paging field the API sends as null reads as `0`.

Filtered down to one record, `get_orders` (by order number), `get_tickets` (by id) and
`get_money_box_payouts` (by payout number) answer with that record alone. The SDK reads it as a
page with one row.

### Values whose names vary

A few responses hold values under names that depend on the request, not on the API: an
external application's settings, and a contact form's properties, starting values and display
properties. These come as lists of name and value pairs,
[`ExternalApplicationSettingMessage`](../src/Dto/Response/Account/ExternalApplicationSettingMessage.php) and [`ContactFormValueMessage`](../src/Dto/Response/Contact/ContactFormValueMessage.php). A value that isn't a single value,
such as a list, is given as its JSON.

No method returns decoded JSON. For a value the SDK doesn't map, read the body as the API sent it,
see [the raw response](#the-raw-response).

### The raw response

An object a service method returns keeps the body of its response.
[`ParsedResponseInterface::getRawResponse()`](../src/Api/ParsedResponseInterface.php) returns it byte for byte as the API sent it, before any parsing.
When the SDK misreads or drops a value, decode the body yourself:

```php
$info = $client->system()->getSystemInfo();

$data = \json_decode((string) $info->getRawResponse(), true);
```

Objects inside it, list items and objects built in code return null. For a method that returns a
list, `true`/`false` or a map, read [`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php).

The body survives `serialize()`, so a cached object keeps its whole response. Two objects with the
same values from different responses aren't equal under `==`.

Why requests and responses are split
------------------------------------

Request and response classes stay separate even when they describe the same thing, because the
shapes differ.

[`PackMessage`](../src/Entity/Order/PackMessage.php) has the dimensions you choose. [`Pack`](../src/Entity/Order/Pack.php) has those plus
what the carrier charged for them. [`CodMessage`](../src/Entity/Order/CodMessage.php) says what to collect.
[`Cod`](../src/Entity/Order/Cod.php) adds what happened to the money: its status and, for a returned
amount, when and why it was returned.

The pairs:

| You send | You receive |
|----------|-------------|
| [`SaveAddressMessage`](../src/Entity/Address/SaveAddressMessage.php) | [`Address`](../src/Entity/Address/Address.php) |
| [`SaveBankAccountMessage`](../src/Entity/BankAccount/SaveBankAccountMessage.php) | [`BankAccount`](../src/Entity/BankAccount/BankAccount.php) |
| [`SavePackTemplateMessage`](../src/Entity/PackTemplate/SavePackTemplateMessage.php) | [`PackTemplate`](../src/Entity/PackTemplate/PackTemplate.php) |
| [`PackMessage`](../src/Entity/Order/PackMessage.php) | [`Pack`](../src/Entity/Order/Pack.php) |
| [`CodMessage`](../src/Entity/Order/CodMessage.php) | [`Cod`](../src/Entity/Order/Cod.php) |
| [`PickupMessage`](../src/Entity/Order/PickupMessage.php) | [`Pickup`](../src/Entity/Order/Pickup.php) |
| [`CreateOrderMessage`](../src/Entity/Order/CreateOrderMessage.php) | [`Order`](../src/Entity/Order/Order.php) |

The docblock of each response class above names the request that writes it, so you can find the pair
from either side.

The main benefit is nullability. A response field can be non-nullable when the API always sends
it. With a single shared class, any field that is optional on one side would have to be optional
on both, and most getters would return a nullable type.

Naming
------

Every class you send ends in `Message`: [`CreateOrderMessage`](../src/Entity/Order/CreateOrderMessage.php), [`SaveAddressMessage`](../src/Entity/Address/SaveAddressMessage.php),
[`GetOrdersMessage`](../src/Dto/Request/Order/GetOrdersMessage.php). The suffix means "a payload".

For the classes you read, it depends. Entity-like classes that exist independently of any call are named
after the thing itself: [`Order`](../src/Entity/Order/Order.php), [`Address`](../src/Entity/Address/Address.php), [`Carrier`](../src/Dto/Response/Carrier.php), [`CourierPoint`](../src/Dto/Response/CourierPoint.php), [`Country`](../src/Dto/Response/Country.php), [`BankAccount`](../src/Entity/BankAccount/BankAccount.php),
[`PackTemplate`](../src/Entity/PackTemplate/PackTemplate.php). Classes that only exist as the answer from one endpoint keep the suffix:
[`OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php), [`AccountInfoMessage`](../src/Dto/Response/Account/AccountInfoMessage.php), [`HeartbeatMessage`](../src/Dto/Response/Util/HeartbeatMessage.php).

When two classes share a short name, like the order's [`PickupMessage`](../src/Entity/Order/PickupMessage.php) and the
collection check [`Dto\Request\Courier\PickupMessage`](../src/Dto/Request/Courier/PickupMessage.php), import one and use the fully qualified name for
the other, or alias it:

```php
use Polkurier\Webservice\Entity\Order\PickupMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage as PickupCheckMessage;
```

Wire names
----------

Each property names its API field in a `#[SerializedName]`, whether or not the two names match:

```php
#[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
private ShipmentTypeEnum $shipmentType,
```

Names like `codbankaccount`, `is_cover_address`, `shipmenttype` and `value_nett` never show up in
your code. The wire names are inconsistent, and keeping them in an attribute means they can change
without a breaking release. [Serialization](serialization.md)
explains how it works.

Each wire name is also a class constant:

```php
CreateOrderMessage::FIELD_SHIPMENT_TYPE;   // 'shipmenttype'
```

The attribute references the constant, so each wire name is written in one place only. The constants
give the API's own field names, e.g. for reading a [`ValidationException`](../src/Exception/ValidationException.php), matching a field in an error from
the API, or building a raw request for debugging.

Enums
-----

Values the API accepts and returns are backed enums under [`Polkurier\Webservice\Enum`](../src/Enum/), grouped by
area:

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
| [`Invoice\InvoiceSummaryFormatEnum`](../src/Enum/Invoice/InvoiceSummaryFormatEnum.php) | PDF or CSV, for an invoice's itemised summary |
| [`Ticket\TicketStatusEnum`](../src/Enum/Ticket/TicketStatusEnum.php), [`TicketAuthorEnum`](../src/Enum/Ticket/TicketAuthorEnum.php), [`TicketOriginEnum`](../src/Enum/Ticket/TicketOriginEnum.php), [`TicketTimelineItemTypeEnum`](../src/Enum/Ticket/TicketTimelineItemTypeEnum.php) | Support |
| [`Contact\ContactFormFieldTypeEnum`](../src/Enum/Contact/ContactFormFieldTypeEnum.php), [`ContactFormSeverityEnum`](../src/Enum/Contact/ContactFormSeverityEnum.php) | Contact form fields |
| [`Webhook\OrderWebhookTypeEnum`](../src/Enum/Webhook/OrderWebhookTypeEnum.php) | What a webhook event says happened to an order |
| [`KnowledgeBase\KnowledgeBaseEntryTypeEnum`](../src/Enum/KnowledgeBase/KnowledgeBaseEntryTypeEnum.php) | Category or article |
| [`TwoFactorToken\TwoFactorNamespaceEnum`](../src/Enum/TwoFactorToken/TwoFactorNamespaceEnum.php) | Which operation a code authorises |
| [`TwoFactorToken\TwoFactorTransportTypeEnum`](../src/Enum/TwoFactorToken/TwoFactorTransportTypeEnum.php) | How a code reaches the customer |

In a response, a value the SDK doesn't know reads as `null`, so every enum field in a response is
nullable. In a list of enum values, unknown values are left out. A value added to the API after
your SDK version was released then doesn't make the whole response unreadable. Requests stay strict:
you can only send the cases the enum has.

After a call, [`PolkurierWebservice::getLastResponseDrift()`](../src/PolkurierWebservice.php) lists each such value, with its enum and
where in the response it was, along with the response's other differences from the SDK (see
[Serialization](serialization.md#responses-that-differ-from-the-sdk)), and [`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php) returns the HTTP response
exactly as the API sent it:

```php
$templates = $client->packTemplates()->getPackTemplates();

foreach ($client->getLastResponseDrift() as $drift) {
    // e.g. UNKNOWN_ENUM_VALUE, "[0].type", "XX", Polkurier\Webservice\Enum\Order\PackTypeEnum
    echo $drift->getType()->value, ' ', $drift->getPath(), ' ', \json_encode($drift->getValue()), ' ',
        $drift->getExpected(), "\n";
}

$json = (string) $client->getLastResponse()?->getBody();
```

With logging on, each unknown value is also logged as a warning, so you know the SDK needs
updating. See [serialization](serialization.md#responses-that-differ-from-the-sdk) for how it works.

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

Request and response classes have no interfaces of their own, only the shared
[`MessageInterface`](../src/Api/MessageInterface.php) and, on the classes service methods return, [`ParsedResponseInterface`](../src/Api/ParsedResponseInterface.php). Wire names, formats and dates
are defined in serializer attributes on the classes themselves, so a different implementation of
an interface wouldn't serialize.

The interfaces under `Api` are the ones where your own implementation changes something.
