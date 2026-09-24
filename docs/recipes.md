Recipes
=======

Worked solutions to the things integrations actually do. Runnable versions of most of these live in
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

Four steps: pick a carrier, describe the shipment, price it, place it.

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

$quote   = $client->orders()->orderValuation($order);   // array — no stable shape
$created = $client->orders()->createOrder($order);      // CreatedOrderMessage

printf(
    "Order %s placed. Waybills: %s. Gross: %.2f PLN\n",
    $created->getOrderNumber(),
    implode(', ', $created->getLabel()) ?: '(pending)',
    $created->getPriceGross(),
);
```

Three things the SDK cannot check for you:

- **`pickup` is carrier-dependent.** Some carriers need a collection date, some also a time window.
  Read what a carrier requires from `availableCarriers(additionalData: true)` rather than assuming.
- **`description` has a length limit.** `CreateOrderMessage::DESCRIPTION_MAX_LENGTH` is the general
  one, and a carrier may impose a shorter. Keep it short, and do not rely on a long value surviving
  in full.
- **Some additional services have prerequisites**, which `availableCarriers(additionalData: true)`
  reports per carrier. Read them from there; they vary by carrier and by account.

> A successful `orderValuation()` is a price, not a dry run. The two calls take the same payload,
> which does not mean a payload that prices cleanly will be accepted for creation.

### The same thing with builders

Better when half the fields are conditional — see [Builders](builders.md):

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

Two approaches, and most integrations want both.

**Let the customer choose from what is available.** `availableCarriers()` is the source of truth
for what this account may ship with:

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

With `additionalData: true` each `Carrier` also carries what it supports — shipment types, pack
limits, pickup requirements, the services it sells, which point map it uses. That is the data the
SDK deliberately does not validate against, and the data you need to build a sensible form.

**Let the price choose.** Quote the same shipment against several carriers and compare:

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

Each quote is an unmapped array; read the price out of it according to what the API returned. Catch
per carrier — a refusal from one is not a failure of the comparison.

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

Two behaviours to know about.

**Zero means off.** An amount of zero is no cash on delivery, and the fields beside it then
describe a collection that is not happening. The SDK models that with
`#[IgnoredWhen(property: 'amount', equals: 0.0)]`, so a zero-amount block is not held to rules that
could not matter — a bank account is not demanded for money that will never be collected.

**The account number is checked properly.** `Validate\Iban` verifies the ISO 13616 check digits, on
either a full IBAN or a bare Polish national number. This is the one place the SDK is deliberately
strict, because the field decides where the money goes.

Reading the money back is a different class: `Response\Order\Cod` says what became of it — status,
payout, return details — where `CodMessage` said what to collect.

```php
$cod = $order->getCod();   // ?Cod — null when the order had none

if ($cod !== null) {
    echo $cod->getStatus();
}
```

That null is real and common — an order placed without cash on delivery has none to report. See
[Serialization — empty values](serialization.md#empty-values-on-the-way-in) for how the SDK
recognises an absent nested object.

---

Pickup points
-------------

Three routes, covered in full in [Services — finding a pickup point](services.md#finding-a-pickup-point--three-ways).
The short version:

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

Putting the chosen point on an order is a field of the address:

```php
$recipient = OrderAddressMessageBuilder::create()
    ->person('Anna Nowak')
    ->city('Poznań')
    ->postcode('61-001')
    ->phone('600700800')
    ->pointId('POZ01A')
    ->build();
```

A recipient collecting from a point needs no street address for several carriers — the SDK knows
this, and `OrderAddressValidator` judges a point-bound address on the point and a phone number
alone.

---

Booking a collection
--------------------

For one shipment:

```php
$pickup = $client->courier()->pickupCourier($pickupMessage);
```

For several at once, gated by availability:

```php
if ($client->orders()->isMultiPickupAvailable()->isAvailable()) {
    $result = $client->orders()->createMultiOrderPickup($request);
}
```

To find out when a carrier can come:

```php
$days = $client->courier()->getCourierPickupTime($whenMessage);   // PickupTimeMessage[]
```

Each entry is a day with the windows available on it. Offer those rather than letting the customer
propose a time the carrier will refuse.

A collection can also be part of the order itself, through `CreateOrderMessage::$pickup`. Which
route to use depends on the carrier: some require the pickup on the order, some accept a separate
booking afterwards. `availableCarriers(additionalData: true)` says which.

---

Labels and protocols
--------------------

`getLabel()` takes a **list** of order numbers and returns one PDF containing all of them — one
call, not one per order:

```php
use Polkurier\Webservice\Request\Order\GetDocumentMessage;

$document = $client->orders()->getLabel(new GetDocumentMessage(
    orderNo: ['POL123456789', 'POL123456790'],
));
```

Two ways to use the result:

```php
file_put_contents('labels.pdf', $document->getDecodedFile());   // bytes
echo $document->getFile();                                      // base64, as it arrived
```

Streaming it to a browser:

```php
return new Response(
    $document->getDecodedFile(),
    200,
    ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="labels.pdf"'],
);
```

A printer language other than PDF, where the carrier supports it:

```php
use Polkurier\Webservice\Enum\Order\LabelFileFormatEnum;

new GetDocumentMessage(orderNo: $numbers, fileFormat: LabelFileFormatEnum::Zpl);
```

`getProtocol()` is the same call for the handover list the courier signs. `getOrderFile()` fetches
a document attached to one order, and `invoices()->getInvoicePdf()` follows the same base64 pattern.

---

Tracking
--------

The cheap call, for a single order:

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

> `tracking: true` is materially slower than the plain call, because the scan history comes from
> the carrier. Leave it off when you only need the current state.

Comparing against known states:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($status->getStatusCode() === OrderStatusCodeEnum::Delivered->value) { … }
```

The status is a string with an enum beside it rather than a typed enum, deliberately: a new status
from the API should be a comparison that does not match, not a response that fails to map.

---

Paging through a list
---------------------

Two loops, because the paged responses are not all the same shape — see
[Messages](messages.md#lists-maps-and-pages).

**When the response carries a page count** (`OrderListMessage`, `MoneyBoxPayoutListMessage`):

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

**When it does not** (`InvoiceListMessage`, `TicketListMessage`, `PrepaidHistoryMessage`) — stop
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

As a generator, which is usually what you want in application code:

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

Page sizes are capped, and the SDK enforces the cap rather than sending a value that will not be
honoured in full: `GetOrdersMessage::MAX_PAGE_SIZE` is 100, `GetTicketsMessage::MAX_PAGE_SIZE` is
250. Asking for more is a `ValidationException`, not a short page you might not notice.

---

Two-factor confirmation
-----------------------

Some operations are guarded depending on the account's settings — changing a bank account,
requesting a payout. The flow:

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

Two recovery paths if the code does not arrive:

```php
use Polkurier\Webservice\Request\Common\TokenMessage;

$client->twoFactorToken()->twoFactorTokenResend(new TokenMessage($token->getId()));
$client->twoFactorToken()->twoFactorTokenChangeTransportMethod($changeMessage);
```

Whether a given call needs this at all is an account setting — read
`account()->getAccountInfo()->getPinLocks()`. The SDK does not guess, because guessing wrong means
either a refused call or an unnecessary code sent to a customer.

---

Paying out collected money
--------------------------

Quote first, then request:

```php
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;

$fee = $client->moneyBox()->calculatePayoutFee();   // MoneyBoxFeeMessage — costs nothing

$payout = $client->moneyBox()->createPayout(new CreateMoneyBoxPayoutMessage(
    payoutType: MoneyBoxPayoutTypeEnum::Account,
    feePaymentType: MoneyBoxFeePaymentTypeEnum::MoneyBox,
    // securityPin / twoFactorToken / twoFactorCode as the account requires
));

printf("Payout %s for %.2f PLN is %s\n",
    $payout->getNumber(), $payout->getAmount(), $payout->getStatus());
```

> This moves real money, and there is no idempotency key. If `createPayout()` raises a
> `ConnectionException`, check `getPayouts()` before doing anything else — do not retry blindly.

`cancelPayout()` works only while the payout has not been sent. Current balances are on
`account()->getAccountInfo()`: `getMoneyBoxBalance()`,
`getMoneyBoxPayoutAvailableBalance()`, `getMoneyBoxToPayoutBalance()`.

---

Retrying safely
---------------

Which failures are worth retrying is in [Errors](errors.md#retrying-safely), along with the
reconcile-then-retry pattern for `createOrder()`. The short rules:

- Reads: retry freely.
- `ValidationException`, `InvalidArgumentException`, `AuthorizationException`: never.
- Writes after a `ConnectionException`: reconcile first. There is no idempotency key.

Adding retries to the transport rather than to each call is a job for a
[custom `ClientFactory`](architecture.md#clientfactoryinterface--your-own-http-client) — but a
blanket retry policy there will retry writes too, so scope it to idempotent methods or to
connection-level failures only.

---

Caching what does not change
----------------------------

Several responses are stable for hours or days and expensive to fetch:

| Call | Typical lifetime |
|------|------------------|
| `courier()->findCourierPoints()`, with only the carriers | Hours. Large. |
| `courier()->getSupportedCountries()` | Days |
| `knowledgeBase()->getIndex()` | Hours |
| `tickets()->getContactTopics()` | Days |
| `system()->getSystemInfo()` | Hours |
| `courier()->availableCarriers()` | Minutes to hours — account-specific |

Two places to put the cache. Around a call, which is simple:

```php
$lockers = $cache->get('polkurier.inpost.lockers', function (ItemInterface $item) use ($client) {
    $item->expiresAfter(3600);

    return $client->courier()->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));
});
```

Or around a whole service, which keeps the caching out of your call sites — see
[Architecture — substituting a service](architecture.md#services--substituting-a-service).

Separately from any of this, set `metadataCache` on `SerializerConfig` in a long-running process.
That is not about API responses; it is the serializer's own reflection, and it is the SDK's biggest
start-up cost. See [Configuration](configuration.md#metadatacache).

---

Health checks
-------------

`heartbeat()` sends no credentials, which makes it the right probe: it answers whether the API is
reachable whatever state your token is in.

```php
try {
    $client->utils()->heartbeat();

    return Status::up();
} catch (ConnectionException) {
    return Status::down('Polkurier unreachable');
}
```

To check credentials as well — for a settings screen, or a deploy smoke test:

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

`testAuthApi()` takes explicit credentials rather than the configured ones, so it can verify
something the user just typed before you save it anywhere.
