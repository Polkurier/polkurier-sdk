Messages and models
===================

Records Polkurier stores, such as addresses, bank accounts, pack templates and orders, are under
[`Entity`](../src/Entity/), one class per record: what you send and what you read back. Everything else you send
is under [`Dto\Request`](../src/Dto/Request/), and everything else you get back under [`Dto\Response`](../src/Dto/Response/). They hold payload classes only.

Requests
--------

A request payload (a **message**) implements [`Api\MessageInterface`](../src/Api/MessageInterface.php). You construct it with named
arguments:

```php
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;

$search = new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
);
```

A DTO under [`Dto`](../src/Dto/) is `final readonly` with public properties, so it can't change between being
validated and being serialized. A record under [`Entity`](../src/Entity/) has a getter and a setter per field, each
setter returning `static`, so one you read back can be changed and sent again.

### Three ways to build one

**1. The constructor, with named arguments.** This is the default and works best when you have
every value up front. You can skip optional parameters in any order.

**2. A [builder](builders.md).** The 24 larger payloads have one. It suits a payload put together
over several steps, or one with many conditional fields:

```php
use Polkurier\Webservice\Builder\Request\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->setCouriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->setSearchQuery($query);
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
no `fromArray()`. A JSON payload keyed by the API's wire names is read by
[`SerializerInterface::decode()`](../src/Api/SerializerInterface.php) instead
([Serialization](serialization.md#using-the-serializer-yourself)).

### Extending a message

The classes of records Polkurier stores aren't `final`: addresses, bank accounts, phone numbers,
pack templates, and orders with their packs, cash on delivery, collection and addresses. Seven
request and response classes are abstract bases the SDK's own messages extend —
[`Dto\Response\PagedListMessage`](../src/Dto/Response/PagedListMessage.php), [`Dto\Request\Common\IdMessage`](../src/Dto/Request/Common/IdMessage.php),
[`Dto\Request\Common\PagedMessage`](../src/Dto/Request/Common/PagedMessage.php), [`Dto\Request\Common\TokenMessage`](../src/Dto/Request/Common/TokenMessage.php),
[`Dto\Request\Account\PinMessage`](../src/Dto/Request/Account/PinMessage.php), [`Dto\Request\Courier\PickupQueryMessage`](../src/Dto/Request/Courier/PickupQueryMessage.php) and
[`Dto\Request\Order\OrderIdMessage`](../src/Dto/Request/Order/OrderIdMessage.php) — and [`AuthorizationMessage`](../src/Dto/Request/AuthorizationMessage.php) carries credentials of
another kind in a subclass. Every other request and response class is `final`. If the API has a
field the SDK doesn't support yet, you can add it to one of the open classes in a subclass. It
will serialize correctly as long as the new property has its own `#[SerializedName]`:

```php
use Polkurier\Webservice\Entity\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

class ExtendedOrder extends Order
{
    #[Serializer\SerializedName('some_new_field')]
    private ?string $someNewField = null;

    public function getSomeNewField(): ?string
    {
        return $this->someNewField;
    }

    public function setSomeNewField(?string $someNewField): static
    {
        $this->someNewField = $someNewField;

        return $this;
    }
}
```

Class-level validation rules are inherited, so the parent's cross-field rules still apply.

A setter for a nested record takes that record's class, so your subclass goes in unchanged and is
kept as it is: [`Order::setSender()`](../src/Entity/Order.php) stores the [`Address`](../src/Entity/Order/Address.php) you give it.

Responses
---------

Response classes are built through their constructors. If a required field is missing from the
reply, mapping fails straight away and the error names the field. You never get an object whose
properties are unset, so a method that returns an [`Order`](../src/Entity/Order.php) always returns a complete one.

Response DTOs are readonly too, with public properties. A record has setters, so it goes
straight back: change the [`Address`](../src/Entity/Address.php) that [`AddressesServiceInterface::getAddresses()`](../src/Api/Service/AddressesServiceInterface.php) returned and
pass it to [`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php), which updates the entry its id names.

### Lists, maps and pages

Apart from a `bool` for calls that only acknowledge, responses come in four shapes:

| Shape | Example | What you get |
|-------|---------|--------------|
| One object | [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) | [`CreatedOrderMessage`](../src/Dto/Response/Order/CreatedOrderMessage.php) |
| A list | [`CourierServiceInterface::getAvailableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | `Carrier[]`, possibly empty |
| A map of strings | [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) | `array<string, string>` |
| A page | [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php) wrapping `Order[]` |

A page carries its rows and its navigation numbers:

```php
$list->result;       // Order[]
$list->totalRows;
$list->totalPages;
$list->currentPage;
$list->pageSize;
```

Five endpoints return pages. [`PagedListMessage`](../src/Dto/Response/PagedListMessage.php) gives every one of them `$totalRows`,
`$currentPage` and `$pageSize`; `$totalPages` is declared per class, and only
[`Order\OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php) and
[`MoneyBox\MoneyBoxPayoutListMessage`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutListMessage.php) have it.

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

One class per record
--------------------

A record Polkurier stores is one class, which you both fill in and read back: [`Address`](../src/Entity/Address.php),
[`BankAccount`](../src/Entity/BankAccount.php), [`PackTemplate`](../src/Entity/PackTemplate.php), [`PhoneNumber`](../src/Entity/PhoneNumber.php), and [`Order`](../src/Entity/Order.php) with its [`PackItem`](../src/Entity/Order/PackItem.php),
[`Cod`](../src/Entity/Order/Cod.php), [`Pickup`](../src/Entity/Order/Pickup.php) and [`Address`](../src/Entity/Order/Address.php), which is also the cover address.

Fields the API only reports carry [`#[ResponseOnly]`](../src/Serializer/Attribute/ResponseOnly.php) and are left out of what the SDK
sends: [`Order::getPriceGross()`](../src/Entity/Order.php), [`Order::getWaybills()`](../src/Entity/Order.php), [`Cod::getStatus()`](../src/Entity/Order/Cod.php),
[`Pickup::getPickupNumber()`](../src/Entity/Order/Pickup.php) and [`Address::isShared()`](../src/Entity/Address.php) among them. A field only the request
needs is required by a validation rule rather than by its PHP type, since the response may not
carry it.

### Request parameters that aren't part of the record

Three requests carry more than the record. Each is a DTO holding the record plus its own fields,
and writes the record's fields at the top level of the payload
([`#[InlineFields]`](../src/Serializer/Attribute/InlineFields.php)):

| Message | Holds | Plus |
|---------|-------|------|
| [`Dto\Request\Order\CreateOrderMessage`](../src/Dto/Request/Order/CreateOrderMessage.php) | [`Order`](../src/Entity/Order.php) | `couponCode` |
| [`Dto\Request\BankAccount\SaveBankAccountMessage`](../src/Dto/Request/BankAccount/SaveBankAccountMessage.php) | [`BankAccount`](../src/Entity/BankAccount.php) | `twoFactorToken`, `twoFactorCode`, `accessPin` |
| [`Dto\Request\Account\SavePhoneNumberMessage`](../src/Dto/Request/Account/SavePhoneNumberMessage.php) | [`PhoneNumber`](../src/Entity/PhoneNumber.php) | `twoFactorToken`, `twoFactorCode` |

[`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) and
[`PackTemplatesServiceInterface::savePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) take the record itself.

Classes that share a short name
------------------------------

When two classes share a short name, like the order's [`Entity\Order\Cod`](../src/Entity/Order/Cod.php) and
[`Dto\Response\Carrier\AdditionalData\Cod`](../src/Dto/Response/Carrier/AdditionalData/Cod.php), which says whether a carrier offers cash on delivery,
import one and use the fully qualified name for the other, or alias it:

```php
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Cod as CarrierCod;
use Polkurier\Webservice\Entity\Order\Cod;
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
Order::FIELD_SHIPMENT_TYPE;   // 'shipmenttype'
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
| [`Account\AccountPaymentTypeEnum`](../src/Enum/Account/AccountPaymentTypeEnum.php) | Whether the account is billed on an invoice or pays from prepaid |
| [`Account\AccountVerificationTypeEnum`](../src/Enum/Account/AccountVerificationTypeEnum.php), [`AccountVerificationStatusEnum`](../src/Enum/Account/AccountVerificationStatusEnum.php) | How an account is being verified, and how far it has got |
| [`Address\AddressTypeEnum`](../src/Enum/Address/AddressTypeEnum.php) | Address book entry kind |
| [`Courier\PointFunctionEnum`](../src/Enum/Courier/PointFunctionEnum.php), [`PointRequirementEnum`](../src/Enum/Courier/PointRequirementEnum.php) | What a pickup point can do |
| [`Courier\CourierMessageTypeEnum`](../src/Enum/Courier/CourierMessageTypeEnum.php), [`CourierMessageSeverityEnum`](../src/Enum/Courier/CourierMessageSeverityEnum.php) | Notice board |
| [`Courier\AdditionalFieldTypeEnum`](../src/Enum/Courier/AdditionalFieldTypeEnum.php) | Carrier-specific extra fields |
| [`Payment\PaymentModeEnum`](../src/Enum/Payment/PaymentModeEnum.php) | What a payment settles |
| [`Payment\PaymentStateEnum`](../src/Enum/Payment/PaymentStateEnum.php) | How far a payment has got |
| [`MoneyBox\MoneyBoxPayoutTypeEnum`](../src/Enum/MoneyBox/MoneyBoxPayoutTypeEnum.php), [`MoneyBoxFeePaymentTypeEnum`](../src/Enum/MoneyBox/MoneyBoxFeePaymentTypeEnum.php) | Payouts |
| [`MoneyBox\MoneyBoxFeeComponentTypeEnum`](../src/Enum/MoneyBox/MoneyBoxFeeComponentTypeEnum.php) | What one part of a payout's fee is charged for |
| [`Invoice\InvoiceTypeEnum`](../src/Enum/Invoice/InvoiceTypeEnum.php) | Invoice kind |
| [`Invoice\InvoiceSummaryFormatEnum`](../src/Enum/Invoice/InvoiceSummaryFormatEnum.php) | PDF or CSV, for an invoice's itemised summary |
| [`Ticket\TicketStatusEnum`](../src/Enum/Ticket/TicketStatusEnum.php), [`TicketAuthorEnum`](../src/Enum/Ticket/TicketAuthorEnum.php), [`TicketOriginEnum`](../src/Enum/Ticket/TicketOriginEnum.php), [`TicketTimelineItemTypeEnum`](../src/Enum/Ticket/TicketTimelineItemTypeEnum.php) | Support |
| [`Contact\ContactFormFieldTypeEnum`](../src/Enum/Contact/ContactFormFieldTypeEnum.php), [`ContactFormSeverityEnum`](../src/Enum/Contact/ContactFormSeverityEnum.php) | Contact form fields |
| [`Webhook\OrderEventTypeEnum`](../src/Enum/Webhook/OrderEventTypeEnum.php) | What a webhook event says happened to an order |
| [`KnowledgeBase\KnowledgeBaseEntryTypeEnum`](../src/Enum/KnowledgeBase/KnowledgeBaseEntryTypeEnum.php) | Category or article |
| [`Coupon\CouponTypeEnum`](../src/Enum/Coupon/CouponTypeEnum.php) | What a coupon takes off the price |
| [`Coupon\CouponUsageTypeEnum`](../src/Enum/Coupon/CouponUsageTypeEnum.php) | Whether a coupon code is usable once in total or once per customer |
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
    echo $drift->type->value, ' ', $drift->path, ' ', \json_encode($drift->value), ' ',
        $drift->expected, "\n";
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
maps it in both directions. When it isn't — a carrier code, a carrier's own tracking status — the
property is a string, because the SDK cannot list what it does not know:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

$order->getStatusCode() === OrderStatusCodeEnum::Delivered
```

On a string field, a new value arrives as itself. On an enum field, it reads as
`null`, as described above. Either way the response still maps, and your code decides what to do
with a value it doesn't recognise.

No interfaces on records
------------------------

A record is a class, not an interface: [`Address`](../src/Entity/Order/Address.php) for an address in an order, [`Address`](../src/Entity/Address.php) for an
address-book entry, and [`Order`](../src/Entity/Order.php), [`Pack`](../src/Entity/Pack.php), [`PackItem`](../src/Entity/Order/PackItem.php), [`PackTemplate`](../src/Entity/PackTemplate.php),
[`BankAccount`](../src/Entity/BankAccount.php), [`PhoneNumber`](../src/Entity/PhoneNumber.php), [`Cod`](../src/Entity/Order/Cod.php) and [`Pickup`](../src/Entity/Order/Pickup.php). Services take
and return these classes.

Wire names, formats and dates are declared in serializer attributes on those classes, and the
validation rules are attributes on them too. An implementation of your own would carry neither, so
it would be sent as an empty block and checked by no rule. Extend the class instead: a subclass
keeps every attribute, and a field it adds is sent only when it carries a `#[SerializedName]` of
its own.

The interfaces under [`Api`](../src/Api/) are the seams where your own implementation changes what
the SDK does: the services, the validator, the client factory and the webhook reader.
