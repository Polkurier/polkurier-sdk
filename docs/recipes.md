Recipes
=======

Runnable versions of most of these recipes are in [`examples/`](../examples), one file per API
method.

- [Placing an order](#placing-an-order)
- [Choosing a carrier](#choosing-a-carrier)
- [Cash on delivery](#cash-on-delivery)
- [Pickup points](#pickup-points)
- [Booking a collection](#booking-a-collection)
- [Labels and protocols](#labels-and-protocols)
- [Tracking](#tracking)
- [Paging through a list](#paging-through-a-list)
- [Two-factor confirmation](#two-factor-confirmation)
- [Paying out collected money](#paying-out-collected-money)
- [Retrying safely](#retrying-safely)
- [Caching what does not change](#caching-what-does-not-change)
- [Health checks](#health-checks)

---

Placing an order
----------------

Pick a carrier, describe the shipment, get a price, then place the order.

```php
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

$order = new Order(
    courier: 'UPS',                       // a Carrier::$serviceCode
    shipmentType: ShipmentTypeEnum::Box,
    packs: [
        new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
    ],
    sender: new Address(
        person: 'Jan Kowalski',
        street: 'Długa',
        city: 'Poznań',
        postcode: '61-001',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        company: 'Acme sp. z o.o.',
        houseNumber: '12',
    ),
    recipient: new Address(
        person: 'Anna Nowak',
        street: 'Krótka',
        city: 'Warszawa',
        postcode: '00-001',
        email: 'anna.nowak@example.com',
        phone: '600700800',
        houseNumber: '5',
        flatNumber: '3',
    ),
    insurance: 500.0,
    courierService: [AdditionalServiceEnum::SmsOnDispatch],
    description: 'Books',
    externalApplicationId: $myApplicationId,   // your application's UUID
    externalOrderId: 'SHOP-1001',              // your own id for this order
);

// The order travels inside the message, which also carries the request-only coupon code.
$message = new CreateOrderMessage(order: $order);

$quotes  = $client->orders()->orderValuation($message);   // ValuationMessage[], one per carrier
$created = $client->orders()->createOrder($message);      // CreatedOrderMessage

printf(
    "Order %s placed. Waybills: %s. Gross: %.2f PLN\n",
    $created->orderNumber,
    implode(', ', $created->label) ?: '(pending)',
    $created->priceGross,
);
```

The SDK cannot check these for you:

- `pickup` depends on the carrier. Some carriers need a collection date, some also need a time
  window. Check what a carrier requires with [`CourierServiceInterface::getAvailableCarriers(additionalData: true)`](../src/Api/Service/CourierServiceInterface.php).
- `description` is required to place an order, and the API cuts it to fit its length limit:
  [`Order::DESCRIPTION_MAX_LENGTH`](../src/Entity/Order.php) bytes, unless the account or the carrier has another.
- `externalApplicationId` and `externalOrderId` are optional. They let you find the
  order again by your own id, e.g. after a timeout (see [retrying safely](#retrying-safely)). The
  SDK refuses one without the other.
- Not every carrier offers every additional service, and some services have prerequisites.
  [`CourierServiceInterface::getAvailableCarriers(additionalData: true)`](../src/Api/Service/CourierServiceInterface.php) lists the services each carrier offers. The ones it
  doesn't list, such as the recipient SMS notifications, are documented with their rules on
  [`AdditionalServiceEnum`](../src/Enum/Order/AdditionalServiceEnum.php).

> [`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) only returns a price. It is not a dry run of [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php). Both take the
> same payload, but a payload that gets a price can still be rejected when you create the order.

### The same thing with builders

Builders are easier when many fields are conditional. See [Builders](builders.md).

```php
use Polkurier\Webservice\Builder\Entity\OrderBuilder;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;

$builder = OrderBuilder::create()
    ->setCourier($carrierCode)
    ->setShipmentType(ShipmentTypeEnum::Box)
    ->setPacks($packs)
    ->setSender($sender)
    ->setRecipient($recipient)
    ->setDescription('Books');

if ($cart->isInsured()) {
    $builder->setInsurance($cart->declaredValue());
}

$created = $client->orders()->createOrder(new CreateOrderMessage(
    order: $builder->build(),
    couponCode: $cart->couponCode(),       // null when there is none
));
```

The coupon code isn't part of the order, so it goes on the message rather than on
[`OrderBuilder`](../src/Builder/Entity/OrderBuilder.php).

---

Choosing a carrier
------------------

There are 2 approaches.

### Let the customer choose

[`CourierServiceInterface::getAvailableCarriers()`](../src/Api/Service/CourierServiceInterface.php) returns the carriers this account can ship with:

```php
use Polkurier\Webservice\Dto\Request\Courier\AvailableCarriersMessage;

$carriers = $client->courier()->getAvailableCarriers(
    new AvailableCarriersMessage(additionalData: true),
);

foreach ($carriers as $carrier) {
    printf(
        "%-24s %-40s %s\n",
        $carrier->serviceCode,
        $carrier->name,
        $carrier->foreignShipments ? 'international' : 'domestic',
    );
}
```

With `additionalData: true`, each [`Carrier`](../src/Dto/Response/Carrier.php) also describes what it supports: shipment types, cash
on delivery and insurance, pickup requirements, the additional services it offers (except the SMS
notifications) and which pickup points it uses. The SDK does not validate against this data. It
describes what a shipping form can offer.

### Let the price decide

One valuation quotes every carrier that can carry the shipment, so compare the quotes it returns:

```php
use Polkurier\Webservice\Builder\Entity\OrderBuilder;
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Response\Order\ValuationMessage;

$quotes = $client->orders()->orderValuation(new CreateOrderMessage(
    order: OrderBuilder::create()
        ->setCourier($anyCode)
        ->setShipmentType($type)
        ->setPacks($packs)
        ->setSender($sender)
        ->setRecipient($recipient)
        ->build(),
));

$quotes = array_filter($quotes, static fn(ValuationMessage $quote) => $quote->shipment);
usort($quotes, static fn(ValuationMessage $a, ValuationMessage $b) => $a->grossPrice <=> $b->grossPrice);

$cheapest = $quotes[0] ?? null;   // place the order with $cheapest->serviceCode as the courier
```

A carrier that can't carry the shipment is quoted at 0, so without the filter on
[`ValuationMessage::$shipment`](../src/Dto/Response/Order/ValuationMessage.php) it would come out cheapest.

---

Cash on delivery
----------------

```php
use Polkurier\Webservice\Builder\Entity\OrderBuilder;
use Polkurier\Webservice\Entity\Order\Cod;

$order = OrderBuilder::create()
    // …
    ->setCod(new Cod(
        amount: 250.0,
        bankAccount: '34102010260000000000000001',
    ))
    ->build();
```

**An amount of zero means no cash on delivery.** The SDK expresses this with
[`#[IgnoredWhen(property: 'amount', equals: 0.0)]`](../src/Validator/Rule/IgnoredWhen.php), so the rest of a zero-amount block is not
validated. For example, no bank account is required.

**The account number is fully checked.** [`Validator\Rule\Iban`](../src/Validator/Rule/Iban.php) verifies the ISO 13616 check digits, for
either a full IBAN or a bare Polish national number. The SDK is strict here because this field
decides where the money goes. Only a Polish account passes, as the API requires.

An order read back carries the same [`Cod`](../src/Entity/Order/Cod.php), with the fields the API only reports filled in:
what happened to the money, its status and, for a returned amount, when and why it came back.

```php
$cod = $order->getCod();   // ?Cod, null when the order had none

if ($cod !== null) {
    echo $cod->getStatus()?->value ?? 'unknown';   // ?CodStatusEnum
}
```

It is often null: an order placed without cash on delivery has nothing to report. See
[Serialization: empty values](serialization.md#empty-values-on-the-way-in) for how the SDK detects
an absent nested object.

---

Pickup points
-------------

[Services: finding a pickup point](services.md#finding-a-pickup-point) says which way suits which case.

```php
// Searching, across carriers
$points = $client->courier()->findCourierPoints(new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
));

// Re-reading one the customer already chose
$point = $client->courier()->getCourierPoint('POZ01A', ['INPOST_PACZKOMAT']);   // ?CourierPoint

// Every point of a carrier, for a local cache
$all = $client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));

// A map widget for the customer
$token = $client->geo()->getMapToken();
```

To send to the chosen point, set it on the recipient address:

```php
use Polkurier\Webservice\Builder\Entity\Order\AddressBuilder;

$recipient = AddressBuilder::create()
    ->setPerson('Anna Nowak')
    ->setStreet('')      // build() needs it, but a point recipient may leave it empty
    ->setCity('Poznań')
    ->setPostcode('61-001')
    ->setEmail('')       // likewise
    ->setPhone('600700800')
    ->setPointId('POZ01A')
    ->build();
```

For several carriers, a recipient collecting from a point needs no street address.
[`OrderValidator`](../src/Validator/Message/OrderValidator.php) accounts for this: an address with a point only
needs the point and a phone number.

---

Booking a collection
--------------------

For one shipment, book the collection with the order, through [`Order::setPickup()`](../src/Entity/Order.php).
[`CourierServiceInterface::getAvailableCarriers(additionalData: true)`](../src/Api/Service/CourierServiceInterface.php) reports each carrier's rules for it.

For several at once, if a collective pickup is available. Availability is decided per shipment,
by its carrier and parcels, so check each one:

```php
// 1. Before placing an order, check the shipment can join a collective pickup.
$check = new MultiPickupAvailabilityRequestMessage(courier: 'DPD', packs: $packs);
if ($client->orders()->isMultiPickupAvailable($check)->available) {
    // 2. Place it for one: pickup: new Pickup(multiPickup: true)
}

// 3. Later, book one collection for the order numbers placed that way.
$result = $client->orders()->createMultiOrderPickup(new MultiPickupRequestMessage(
    pickupDate: new DateTimeImmutable('+1 weekday'),
    pickupTimeFrom: new DateTimeImmutable('10:00'),
    pickupTimeTo: new DateTimeImmutable('14:00'),
    orders: ['12345-678', '12345-679'],
));
```

To find out when a carrier can come:

```php
$days = $client->courier()->getCourierPickupTime($whenMessage);   // PickupTimeMessage[]
$day  = $client->courier()->pickupCourier($pickupMessage);        // one day, PickupResponseMessage
```

Each entry is a day with its available time windows. The carrier refuses a time outside them.
Neither books anything.

---

Labels and protocols
--------------------

[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) takes a **list** of order numbers and returns a single PDF with all their labels, so
one call covers them all:

```php
use Polkurier\Webservice\Dto\Request\Order\GetDocumentMessage;

$document = $client->orders()->getLabel(new GetDocumentMessage(
    orderNo: ['12345-678', '12345-679'],
));
```

`$file` holds the document's bytes:

```php
file_put_contents('labels.pdf', $document->file);
```

To stream it to a browser:

```php
return new Response(
    $document->file,
    200,
    ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="labels.pdf"'],
);
```

A printer format other than PDF works for one order number at a time, if the carrier supports it:

```php
use Polkurier\Webservice\Enum\Order\LabelFileFormatEnum;

new GetDocumentMessage(orderNo: ['12345-678'], fileFormat: LabelFileFormatEnum::Zpl);
```

[`OrdersServiceInterface::getProtocol()`](../src/Api/Service/OrdersServiceInterface.php) takes the same request and returns the handover list the courier signs. One unknown
number among several fails the call.
[`OrdersServiceInterface::getOrderFile()`](../src/Api/Service/OrdersServiceInterface.php) fetches a document attached to one order.
[`InvoicesServiceInterface::getInvoicePdf()`](../src/Api/Service/InvoicesServiceInterface.php) returns base64 in the same way.

---

Tracking
--------

The basic call, for a single order:

```php
use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;

$status = $client->orders()->getStatus(new GetStatusMessage(orderNo: '12345-678'));
```

With the carrier's scan history:

```php
$status = $client->orders()->getStatus(new GetStatusMessage(
    orderNo: '12345-678',
    tracking: true,
));
```

> `tracking: true` is much slower than the plain call.

To compare against known states:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($status->statusCode === OrderStatusCodeEnum::Delivered) { … }
```

The status code is the enum case, or null for a value the SDK doesn't know. The response still
maps, and the unknown value is recorded as drift.

---

Paging through a list
---------------------

The paged responses do not all have the same shape, so there are 2 loops. See
[Messages](messages.md#lists-maps-and-pages).

**When the response has a page count** ([`OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php), [`MoneyBoxPayoutListMessage`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutListMessage.php)):

```php
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;

$page = 1;

do {
    $list = $client->orders()->getOrders(new GetOrdersMessage(
        dateFrom: new DateTimeImmutable('-30 days'),
        pageSize: 100,
        page: $page,
    ));

    foreach ($list->result as $order) {
        // …
    }

    $page++;
} while ($page <= $list->totalPages);
```

**When it does not** ([`InvoiceListMessage`](../src/Dto/Response/Invoice/InvoiceListMessage.php), [`TicketListMessage`](../src/Dto/Response/Ticket/TicketListMessage.php), [`PrepaidHistoryMessage`](../src/Dto/Response/Payment/PrepaidHistoryMessage.php)), stop
when a page comes back short:

```php
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;

$page = 1;
$size = 100;

do {
    $list = $client->invoices()->getInvoices(new GetInvoicesMessage(
        type: InvoiceTypeEnum::Invoice,
        pageSize: $size,
        page: $page,
    ));
    $rows = $list->result;

    foreach ($rows as $invoice) {
        // …
    }

    $page++;
} while (count($rows) === $size);
```

In application code a generator is usually more convenient:

```php
/** @return iterable<Order> */
function allOrders(PolkurierWebservice $client, GetOrdersMessageBuilder $filter): iterable
{
    $page = 1;

    do {
        $list = $client->orders()->getOrders($filter->setPage($page)->setPageSize(100)->build());

        yield from $list->result;

        $page++;
    } while ($page <= $list->totalPages);
}
```

Page sizes have a maximum, and the SDK checks it before sending: [`GetOrdersMessage::MAX_PAGE_SIZE`](../src/Dto/Request/Order/GetOrdersMessage.php)
is 100 and [`GetTicketsMessage::MAX_PAGE_SIZE`](../src/Dto/Request/Ticket/GetTicketsMessage.php) is 250. A larger value throws a [`ValidationException`](../src/Exception/ValidationException.php).

---

Two-factor confirmation
-----------------------

Depending on the account's settings, some operations need a confirmation code, e.g. changing a
bank account or requesting a payout. The flow:

```php
use Polkurier\Webservice\Dto\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;

// 1. Register against the operation the code will authorise.
$token = $client->twoFactorToken()->register(
    new RegisterMessage(namespace: TwoFactorNamespaceEnum::ChangeBankAccountNumber),
);

printf("Code sent to %s, valid until %s\n",
    $token->transportMethod->name ?? 'your phone',   // e.g. a masked phone number
    $token->expiresAt?->format('H:i'),
);

// 2. The customer reads the code off their phone and gives it to you.

// 3. Either verify it here …
$validity = $client->twoFactorToken()->verify(
    new VerifyMessage(token: $token->id, code: $entered),
);

// … or pass the token and code into the guarded call itself.
```

If the code does not arrive, you can resend it, or switch to another delivery method the token
lists:

```php
use Polkurier\Webservice\Dto\Request\TwoFactorToken\ChangeTransportMethodMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\ResendMessage;

$client->twoFactorToken()->resend(new ResendMessage($token->id));

foreach ($token->availableMethods as $method) {   // TransportMethodMessage[]
    // Offer $method->name. Its type is a TwoFactorTransportTypeEnum for your own labels.
}

$token = $client->twoFactorToken()->changeTransportMethod(
    new ChangeTransportMethodMessage(token: $token->id, method: $chosen->id),
);
```

A bank account change takes the security PIN instead while
[`AccountInfoMessage::$pinLocks`](../src/Dto/Response/Account/AccountInfoMessage.php) reports bank account editing as locked. The SDK does not
guess: a wrong guess means either a refused call or a code sent to the customer for no reason.

---

Paying out collected money
--------------------------

The fee can be checked before the payout is requested:

```php
use Polkurier\Webservice\Dto\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;

$config = $client->moneyBox()->getMoneyBoxConfig();         // MoneyBoxConfigMessage, the saved destination
$fee = $client->moneyBox()->calculatePayoutFee();   // MoneyBoxFeeMessage, costs nothing

$payout = $client->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
    payoutType: MoneyBoxPayoutTypeEnum::Account,
    feePaymentType: MoneyBoxFeePaymentTypeEnum::MoneyBox,
    bankAccountNumber: $config->bankAccountNumber,   // must be a saved bank account
));

printf("Payout %s for %.2f PLN is %s\n",
    $payout->number, $payout->amount, $payout->status->value ?? '');
```

> This moves real money, and there is no idempotency key. If [`MoneyBoxServiceInterface::createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) throws a
> [`ConnectionException`](../src/Exception/ConnectionException.php), only [`MoneyBoxServiceInterface::getPayouts()`](../src/Api/Service/MoneyBoxServiceInterface.php) shows whether the payout was made, and a
> blind retry can pay out twice.

[`MoneyBoxServiceInterface::cancelPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) only works while [`MoneyBoxPayoutMessage::$cancellable`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutMessage.php) is true. Current balances are on
[`AccountServiceInterface::getAccountInfo()`](../src/Api/Service/AccountServiceInterface.php): [`AccountInfoMessage::$moneyBoxBalance`](../src/Dto/Response/Account/AccountInfoMessage.php),
[`AccountInfoMessage::$moneyBoxPayoutAvailableBalance`](../src/Dto/Response/Account/AccountInfoMessage.php) and
[`AccountInfoMessage::$moneyBoxToPayoutBalance`](../src/Dto/Response/Account/AccountInfoMessage.php).

---

Retrying safely
---------------

[Errors](errors.md#retrying-safely) lists which failures can be retried, and shows the
reconcile-then-retry pattern for [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php). In short, reads can be repeated, and a
[`ValidationException`](../src/Exception/ValidationException.php), [`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php) or [`AuthorizationException`](../src/Exception/AuthorizationException.php) comes back the same way
on a retry. A write that ended in a [`ConnectionException`](../src/Exception/ConnectionException.php) may have gone through. There is no idempotency
key, but an order placed with your own external ids can be looked up by them.

A retry policy can also sit at the transport level, in a
[custom `ClientFactory`](architecture.md#clientfactoryinterface-your-own-http-client). It then
applies to writes as well as reads.

---

Caching what does not change
----------------------------

Several responses stay the same for hours or days and are slow to fetch:

| Call | Typical lifetime |
|------|------------------|
| [`CourierServiceInterface::findCourierPoints()`](../src/Api/Service/CourierServiceInterface.php), with only the carriers | Hours. Large. |
| [`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) | Days |
| [`KnowledgeBaseServiceInterface::getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | Hours |
| [`TicketsServiceInterface::getContactTopics()`](../src/Api/Service/TicketsServiceInterface.php) | Days. Account-specific |
| [`SystemServiceInterface::getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) | Hours |
| [`CourierServiceInterface::getAvailableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | Minutes to hours. Account-specific |

You can cache around a single call, which is the simplest option:

```php
$lockers = $cache->get('polkurier.inpost.lockers', function (ItemInterface $item) use ($client) {
    $item->expiresAfter(3600);

    return $client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));
});
```

Or around a whole service, which keeps caching out of your call sites. See
[Architecture: substituting a service](architecture.md#substituting-a-service).

`metadataCache` on [`SerializerConfig`](../src/Serializer/SerializerConfig.php) is a different cache. It holds the serializer's reflection data, not
API responses, and saves the SDK's biggest start-up cost in a long-running process. See
[Configuration](configuration.md#metadatacache).

---

Health checks
-------------

[`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) sends no credentials, so it tells you whether the API is reachable regardless of
your token, which makes it a fit for a health probe.

```php
try {
    $client->utils()->heartbeat();

    return Status::up();
} catch (ConnectionException) {
    return Status::down('Polkurier unreachable');
}
```

To check credentials as well, e.g. on a settings screen or in a deploy smoke test:

```php
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Exception\AuthorizationException;

try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $token, login: $login));

    return true;
} catch (AuthorizationException) {
    return false;
}
```

[`UtilsServiceInterface::testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) takes credentials as an argument instead of using the configured ones, so you can
check what the user just typed before you save it.
