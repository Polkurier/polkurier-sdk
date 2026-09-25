Recipes
=======

Solutions for common integration tasks. Runnable versions of most of them are in
[`examples/`](../examples), one file per API method.

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
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;

$order = new CreateOrderMessage(
    courier: 'UPS',                       // a Carrier::getServiceCode()
    shipmentType: ShipmentTypeEnum::Box,
    packs: [
        new PackMessage(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
    ],
    sender: new OrderAddressMessage(
        person: 'Jan Kowalski',
        street: 'Długa',
        city: 'Poznań',
        postcode: '61-001',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        company: 'Acme sp. z o.o.',
        houseNumber: '12',
    ),
    recipient: new OrderAddressMessage(
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
    courierService: [AdditionalServiceEnum::SmsNotificationRecipient],
    description: 'Books',
);

$quote   = $client->orders()->orderValuation($order);   // array, no stable shape
$created = $client->orders()->createOrder($order);      // CreatedOrderMessage

printf(
    "Order %s placed. Waybills: %s. Gross: %.2f PLN\n",
    $created->getOrderNumber(),
    implode(', ', $created->getLabel()) ?: '(pending)',
    $created->getPriceGross(),
);
```

The SDK cannot check these for you:

- `pickup` depends on the carrier. Some carriers need a collection date, some also need a time
  window. Check what a carrier requires with `availableCarriers(additionalData: true)`.
- `description` has a length limit. `CreateOrderMessage::DESCRIPTION_MAX_LENGTH` is the general
  one, and a carrier may set a lower one. Keep it short.
- Some additional services have prerequisites. `availableCarriers(additionalData: true)` lists them
  per carrier, and they vary by carrier and by account. The recipient SMS notifications are the
  exception: they are not listed there, and their rules are documented on `AdditionalServiceEnum`.

> `orderValuation()` only returns a price. It is not a dry run of `createOrder()`. Both take the
> same payload, but a payload that gets a price can still be rejected when you create the order.

### The same thing with builders

Builders are easier when many fields are conditional. See [Builders](builders.md).

```php
use Polkurier\Webservice\Builder\Order\CreateOrderMessageBuilder;

$builder = CreateOrderMessageBuilder::create()
    ->courier($carrierCode)
    ->shipmentType(ShipmentTypeEnum::Box)
    ->packs($packs)
    ->sender($sender)
    ->recipient($recipient);

if ($cart->isInsured()) {
    $builder->insurance($cart->declaredValue());
}

if ($cart->couponCode() !== null) {
    $builder->couponCode($cart->couponCode());
}

$created = $client->orders()->createOrder($builder->build());
```

---

Choosing a carrier
------------------

There are 2 approaches. Most integrations use both.

### Let the customer choose

`availableCarriers()` returns the carriers this account can ship with:

```php
use Polkurier\Webservice\Request\Courier\AvailableCarriersMessage;

$carriers = $client->courier()->availableCarriers(
    new AvailableCarriersMessage(additionalData: true),
);

foreach ($carriers as $carrier) {
    printf(
        "%-24s %-40s %s\n",
        $carrier->getServiceCode(),
        $carrier->getName(),
        $carrier->isForeignShipments() ? 'international' : 'domestic',
    );
}
```

With `additionalData: true`, each `Carrier` also describes what it supports: shipment types, pack
limits, pickup requirements, the additional services it offers (except the SMS notifications) and
which point map it uses. The SDK does not validate against this data. Use it to build your
shipping form.

### Let the price decide

Get a quote for the same shipment from several carriers and compare:

```php
$quotes = [];

foreach ($candidateCodes as $code) {
    try {
        $quotes[$code] = $client->orders()->orderValuation(
            CreateOrderMessageBuilder::create()
                ->courier($code)
                ->shipmentType($type)
                ->packs($packs)
                ->sender($sender)
                ->recipient($recipient)
                ->build(),
        );
    } catch (WebserviceException) {
        // This carrier will not take it. Skip.
    }
}
```

Each quote is an unmapped array, so read the price from whatever structure the API returned. Catch
exceptions per carrier, so that one carrier refusing the shipment does not stop the comparison.

---

Cash on delivery
----------------

```php
use Polkurier\Webservice\Request\Order\CodMessage;

$order = CreateOrderMessageBuilder::create()
    // …
    ->cod(new CodMessage(
        amount: 250.0,
        bankAccount: '34102010260000000000000001',
    ))
    ->build();
```

**An amount of zero means no cash on delivery.** The SDK expresses this with
`#[IgnoredWhen(property: 'amount', equals: 0.0)]`, so the rest of a zero-amount block is not
validated. For example, no bank account is required.

**The account number is fully checked.** `Validate\Iban` verifies the ISO 13616 check digits, for
either a full IBAN or a bare Polish national number. The SDK is strict here because this field
decides where the money goes.

When you read an order back, cash on delivery comes as a different class. `Response\Order\Cod`
reports what happened to the money (status, payout, return details), while `CodMessage` describes
what to collect.

```php
$cod = $order->getCod();   // ?Cod, null when the order had none

if ($cod !== null) {
    echo $cod->getStatus();
}
```

Expect null often: an order placed without cash on delivery has nothing to report. See
[Serialization: empty values](serialization.md#empty-values-on-the-way-in) for how the SDK detects
an absent nested object.

---

Pickup points
-------------

There are 3 ways to do this. [Services: finding a pickup point](services.md#finding-a-pickup-point)
covers them in full. In short:

```php
// Searching, across carriers
$points = $client->courier()->findCourierPoints(new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
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
$recipient = OrderAddressMessageBuilder::create()
    ->person('Anna Nowak')
    ->city('Poznań')
    ->postcode('61-001')
    ->phone('600700800')
    ->pointId('POZ01A')
    ->build();
```

For several carriers, a recipient collecting from a point needs no street address.
`OrderAddressValidator` accounts for this: an address with a point only needs the point and a phone
number.

---

Booking a collection
--------------------

For one shipment:

```php
$pickup = $client->courier()->pickupCourier($pickupMessage);
```

For several at once, if multi-pickup is available:

```php
if ($client->orders()->isMultiPickupAvailable()->isAvailable()) {
    $result = $client->orders()->createMultiOrderPickup($request);
}
```

To find out when a carrier can come:

```php
$days = $client->courier()->getCourierPickupTime($whenMessage);   // PickupTimeMessage[]
```

Each entry is a day with its available time windows. Offer these to the customer, so they cannot
pick a time the carrier will refuse.

You can also book the collection as part of the order, through `CreateOrderMessage::$pickup`. Which
option to use depends on the carrier. Some require the pickup on the order, others accept a separate
booking later. `availableCarriers(additionalData: true)` tells you which.

---

Labels and protocols
--------------------

`getLabel()` takes a **list** of order numbers and returns a single PDF with all their labels. You
do not need one call per order:

```php
use Polkurier\Webservice\Request\Order\GetDocumentMessage;

$document = $client->orders()->getLabel(new GetDocumentMessage(
    orderNo: ['POL123456789', 'POL123456790'],
));
```

You can read the result in 2 forms:

```php
file_put_contents('labels.pdf', $document->getDecodedFile());   // bytes
echo $document->getFile();                                      // base64, as it arrived
```

To stream it to a browser:

```php
return new Response(
    $document->getDecodedFile(),
    200,
    ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="labels.pdf"'],
);
```

For a printer format other than PDF, if the carrier supports it:

```php
use Polkurier\Webservice\Enum\Order\LabelFileFormatEnum;

new GetDocumentMessage(orderNo: $numbers, fileFormat: LabelFileFormatEnum::Zpl);
```

`getProtocol()` works the same way and returns the handover list the courier signs.
`getOrderFile()` fetches a document attached to one order. `invoices()->getInvoicePdf()` also
returns base64 in the same way.

---

Tracking
--------

The basic call, for a single order:

```php
use Polkurier\Webservice\Request\Order\GetStatusMessage;

$status = $client->orders()->getStatus(new GetStatusMessage(orderNo: 'POL123456789'));
```

With the carrier's scan history:

```php
$status = $client->orders()->getStatus(new GetStatusMessage(
    orderNo: 'POL123456789',
    tracking: true,
));
```

> `tracking: true` is much slower than the plain call. Leave it off when you only need the current
> state.

To compare against known states:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($status->getStatusCode() === OrderStatusCodeEnum::Delivered->value) { … }
```

The status is a plain string, with an enum you can compare it to. If the API adds a new status,
the comparison just does not match, and the response still maps.

---

Paging through a list
---------------------

The paged responses do not all have the same shape, so there are 2 loops. See
[Messages](messages.md#lists-maps-and-pages).

**When the response has a page count** (`OrderListMessage`, `MoneyBoxPayoutListMessage`):

```php
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

$page = 1;

do {
    $list = $client->orders()->getOrders(new GetOrdersMessage(
        dateFrom: new DateTimeImmutable('-30 days'),
        pageSize: 100,
        page: $page,
    ));

    foreach ($list->getResult() as $order) {
        // …
    }

    $page++;
} while ($page <= $list->getTotalPages());
```

**When it does not** (`InvoiceListMessage`, `TicketListMessage`, `PrepaidHistoryMessage`), stop
when a page comes back short:

```php
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;

$page = 1;
$size = 100;

do {
    $list = $client->invoices()->getInvoices(new GetInvoicesMessage(
        type: InvoiceTypeEnum::Invoice,
        pageSize: $size,
        page: $page,
    ));
    $rows = $list->getResult();

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
        $list = $client->orders()->getOrders($filter->page($page)->pageSize(100)->build());

        yield from $list->getResult();

        $page++;
    } while ($page <= $list->getTotalPages());
}
```

Page sizes have a maximum, and the SDK checks it before sending: `GetOrdersMessage::MAX_PAGE_SIZE`
is 100 and `GetTicketsMessage::MAX_PAGE_SIZE` is 250. A larger value throws a `ValidationException`.

---

Two-factor confirmation
-----------------------

Depending on the account's settings, some operations need a confirmation code, e.g. changing a
bank account or requesting a payout. The flow:

```php
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Request\TwoFactorToken\VerifyMessage;

// 1. Register against the operation the code will authorise.
$token = $client->twoFactorToken()->twoFactorTokenRegister(
    new RegisterMessage(namespace: TwoFactorNamespaceEnum::ChangeIban),
);

printf("Code sent to %s, valid until %s\n",
    $token->getPhoneNumber(),
    $token->getExpiresAt()->format('H:i'),
);

// 2. The customer reads the code off their phone and gives it to you.

// 3. Either verify it here …
$validity = $client->twoFactorToken()->twoFactorTokenVerify(
    new VerifyMessage(token: $token->getId(), code: $entered),
);

// … or pass the token and code into the guarded call itself.
```

If the code does not arrive, you can resend it or switch the delivery method:

```php
use Polkurier\Webservice\Request\Common\TokenMessage;

$client->twoFactorToken()->twoFactorTokenResend(new TokenMessage($token->getId()));
$client->twoFactorToken()->twoFactorTokenChangeTransportMethod($changeMessage);
```

Whether a call needs this depends on the account settings. Read
`account()->getAccountInfo()->getPinLocks()`. The SDK does not guess: a wrong guess means either a
refused call or a code sent to the customer for no reason.

---

Paying out collected money
--------------------------

Check the fee first, then request the payout:

```php
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;

$fee = $client->moneyBox()->calculatePayoutFee();   // MoneyBoxFeeMessage, costs nothing

$payout = $client->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
    payoutType: MoneyBoxPayoutTypeEnum::Account,
    feePaymentType: MoneyBoxFeePaymentTypeEnum::MoneyBox,
    // securityPin / twoFactorToken / twoFactorCode as the account requires
));

printf("Payout %s for %.2f PLN is %s\n",
    $payout->getNumber(), $payout->getAmount(), $payout->getStatus());
```

> This moves real money, and there is no idempotency key. If `createPayout()` throws a
> `ConnectionException`, check `getPayouts()` before you do anything else. Do not just retry.

`cancelPayout()` only works while the payout has not been sent. Current balances are on
`account()->getAccountInfo()`: `getMoneyBoxBalance()`, `getMoneyBoxPayoutAvailableBalance()` and
`getMoneyBoxToPayoutBalance()`.

---

Retrying safely
---------------

[Errors](errors.md#retrying-safely) lists which failures can be retried, and shows the
reconcile-then-retry pattern for `createOrder()`. In short:

- Reads: retry freely.
- `ValidationException`, `InvalidArgumentException`, `AuthorizationException`: never.
- Writes after a `ConnectionException`: reconcile first. There is no idempotency key.

To retry at the transport level instead of around each call, use a
[custom `ClientFactory`](architecture.md#clientfactoryinterface-your-own-http-client). A retry
policy there applies to writes as well, so limit it to idempotent methods or to connection-level
failures.

---

Caching what does not change
----------------------------

Several responses stay the same for hours or days and are slow to fetch:

| Call | Typical lifetime |
|------|------------------|
| `courier()->findCourierPoints()`, with only the carriers | Hours. Large. |
| `courier()->getSupportedCountries()` | Days |
| `knowledgeBase()->getIndex()` | Hours |
| `tickets()->getContactTopics()` | Days |
| `system()->getSystemInfo()` | Hours |
| `courier()->availableCarriers()` | Minutes to hours. Account-specific |

You can cache around a single call, which is the simplest option:

```php
$lockers = $cache->get('polkurier.inpost.lockers', function (ItemInterface $item) use ($client) {
    $item->expiresAfter(3600);

    return $client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));
});
```

Or around a whole service, which keeps caching out of your call sites. See
[Architecture: substituting a service](architecture.md#substituting-a-service).

Separately, set `metadataCache` on `SerializerConfig` in a long-running process. This caches the
serializer's reflection data, not API responses, and it is the SDK's biggest start-up cost. See
[Configuration](configuration.md#metadatacache).

---

Health checks
-------------

`heartbeat()` sends no credentials, so it tells you whether the API is reachable regardless of
your token. Use it as the health probe.

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
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $token, login: $login));

    return true;
} catch (AuthorizationException) {
    return false;
}
```

`testAuthApi()` takes credentials as an argument instead of using the configured ones, so you can
check what the user just typed before you save it.
