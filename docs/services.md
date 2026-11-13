Services
========

The client has one service per functional area and a method for each API endpoint. Each accessor
returns an interface from [`Polkurier\Webservice\Api\Service\`](../src/Api/Service/), and that interface is the reference for
its methods: each method's docblock lists the endpoint it calls, a sample payload, the possible
success responses and the most common errors. The [API method index](api-methods.md) lists every
method against the endpoint it calls.

| Accessor | Interface | Covers |
|----------|-----------|--------|
| `courier()` | [`CourierServiceInterface`](../src/Api/Service/CourierServiceInterface.php) | Carriers, collections, pickup points, country coverage |
| `orders()` | [`OrdersServiceInterface`](../src/Api/Service/OrdersServiceInterface.php) | Pricing, placing, tracking, cancelling, documents |
| `addresses()` | [`AddressesServiceInterface`](../src/Api/Service/AddressesServiceInterface.php) | The account's address book |
| `bankAccounts()` | [`BankAccountsServiceInterface`](../src/Api/Service/BankAccountsServiceInterface.php) | Bank accounts money box payouts can be sent to |
| `packTemplates()` | [`PackTemplatesServiceInterface`](../src/Api/Service/PackTemplatesServiceInterface.php) | Saved parcel templates |
| `geo()` | [`GeoServiceInterface`](../src/Api/Service/GeoServiceInterface.php) | City lookup and map tokens |
| `account()` | [`AccountServiceInterface`](../src/Api/Service/AccountServiceInterface.php) | Profile, balances, preferences, credentials, phone numbers |
| `accountVerification()` | [`AccountVerificationServiceInterface`](../src/Api/Service/AccountVerificationServiceInterface.php) | Identity verification |
| `invoices()` | [`InvoicesServiceInterface`](../src/Api/Service/InvoicesServiceInterface.php) | Invoices and their documents |
| `payments()` | [`PaymentsServiceInterface`](../src/Api/Service/PaymentsServiceInterface.php) | Online payments and the prepaid balance |
| `moneyBox()` | [`MoneyBoxServiceInterface`](../src/Api/Service/MoneyBoxServiceInterface.php) | Collected funds and withdrawing them |
| `tickets()` | [`TicketsServiceInterface`](../src/Api/Service/TicketsServiceInterface.php) | Support tickets and contact forms |
| `knowledgeBase()` | [`KnowledgeBaseServiceInterface`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | Help articles |
| `twoFactorToken()` | [`TwoFactorTokenServiceInterface`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Confirmation codes for guarded operations |
| `system()` | [`SystemServiceInterface`](../src/Api/Service/SystemServiceInterface.php) | Service limits, carrier notices, coupon checks |
| `utils()` | [`UtilsServiceInterface`](../src/Api/Service/UtilsServiceInterface.php) | Reachability and credential checks |

Reading a method signature
--------------------------

Method signatures follow three patterns:

```php
availableCarriers(?AvailableCarriersMessage $message = null): array   // all filters optional
createOrder(CreateOrderMessage $message): CreatedOrderMessage         // payload required
getBankAccounts(): array                                             // no parameters at all
```

A method that returns a [`CreatedOrderMessage`](../src/Dto/Response/Order/CreatedOrderMessage.php) either returns one or throws. It never returns null
because the API sent something unexpected. Two methods can return null: [`CourierServiceInterface::getCourierPoint()`](../src/Api/Service/CourierServiceInterface.php), for an id
that matches no point, and [`AccountVerificationServiceInterface::getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php), for an account that has
never started verification.

Every method can throw [`ConnectionException`](../src/Exception/ConnectionException.php), [`MalformedResponseException`](../src/Exception/MalformedResponseException.php),
[`WebserviceException`](../src/Exception/WebserviceException.php) or its subclass [`AuthorizationException`](../src/Exception/AuthorizationException.php), and
[`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php). A method that takes a message can also throw
[`ValidationException`](../src/Exception/ValidationException.php). See [Errors](errors.md).

An order from start to finish
-----------------------------

```
courier()->availableCarriers()        which carriers, and what each one supports
        │
orders()->orderValuation()            the price, before committing to it
        │
orders()->createOrder()               places the order and returns its number
        │
orders()->getLabel()                  the label to stick on the parcel
        │
orders()->createMultiOrderPickup()    one collection for several orders, unless each
        │                             order booked its own, see Booking a collection
        │
orders()->getStatus(), getOrders()    where it is now; webhooks report changes
        │
        └── orders()->cancelOrder()   unless the carrier refuses
```

Choosing a carrier
------------------

| Method | What it does |
|--------|--------------|
| [`CourierServiceInterface::availableCarriers()`](../src/Api/Service/CourierServiceInterface.php) | Lists the carriers this account may ship with |
| [`SystemServiceInterface::getCouriersMessages()`](../src/Api/Service/SystemServiceInterface.php) | Current carrier notices: delays, suspensions and general information |
| [`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) | Every country the service supports, with its regions |
| [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) | The countries one international carrier delivers to |

[`CourierServiceInterface::availableCarriers()`](../src/Api/Service/CourierServiceInterface.php) lists the carriers. A [`Carrier`](../src/Dto/Response/Carrier.php)'s `serviceCode` is what
[`Order::setCourier()`](../src/Entity/Order.php) takes. With `additionalData: true`, the response also lists what each
carrier supports: shipment types, cash on delivery and insurance, whether it needs a pickup date,
which additional services it offers, and which pickup points it uses. The SDK doesn't check an order
against this data.

```php
$carriers = $client->courier()->availableCarriers(
    new AvailableCarriersMessage(additionalData: true),
);
```

[`SystemServiceInterface::getCouriersMessages()`](../src/Api/Service/SystemServiceInterface.php) returns carrier notices (delays, suspensions, general information), each
an alert or a message ([`CourierMessageTypeEnum`](../src/Enum/Courier/CourierMessageTypeEnum.php)) with a display severity
([`CourierMessageSeverityEnum`](../src/Enum/Courier/CourierMessageSeverityEnum.php)).

[`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) returns the countries Polkurier serves, as [`Country`](../src/Dto/Response/Country.php) objects with
their regions. [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) returns the countries a particular carrier delivers
to, as a flat map of codes to names. The first suits a form, the second a check of one carrier's
destinations.

Finding a pickup point
----------------------

| Method | What it does |
|--------|--------------|
| [`CourierServiceInterface::findCourierPoints()`](../src/Api/Service/CourierServiceInterface.php) | Finds pickup points across one or more carriers |
| [`CourierServiceInterface::getCourierPoint()`](../src/Api/Service/CourierServiceInterface.php) | Fetches one pickup point by its id, or null |
| [`GeoServiceInterface::getMapToken()`](../src/Api/Service/GeoServiceInterface.php) | A short-lived token for the pickup point map widget |

```
Who picks the point?
├── the customer, on a map ............................ 3. geo()->getMapToken()
└── your code
    ├── near a place, across carriers ................. 1. findCourierPoints()
    ├── all of a carrier's points, for a cache ........ 1. findCourierPoints(), without a query
    └── a saved id: does it still exist? .............. 2. getCourierPoint()
```

**1. Search across carriers.** Search by a phrase, optionally keeping only points with given
functions. A search returns up to the first 1,000 matches and ignores `page` and `limit`:

```php
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;

$points = $client->courier()->findCourierPoints(new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
));
```

The carrier list takes all the codes in a carrier's
[`PointsMap::$availableProviders`](../src/Dto/Response/Carrier/AdditionalData/PointsMap.php). A code on its own may match nothing.

Without a search query, it returns every point of the given carriers, which suits a
local cache. For InPost that is over 30,000 points, so cache the result instead of fetching it for
every page. To page through them, set `limit` with `page`; the SDK refuses `page` alone.

**2. Look one up by id.** This checks that a point the customer already chose still exists:

```php
$point = $client->courier()->getCourierPoint('POZ01A', ['INPOST_PACZKOMAT']);

if ($point === null) {
    // The id is unknown to that carrier.
}
```

The carrier list is required even if the id looks unique.

**3. Show the customer a map.** [`GeoServiceInterface::getMapToken()`](../src/Api/Service/GeoServiceInterface.php) returns a short-lived token for the
pickup point map widget, which runs in the browser and reports the chosen point back to your page.
The point goes into the order's address. The map is for a customer picking the point, the methods
above for code that does.

Pricing and placing an order
----------------------------

| Method | What it does |
|--------|--------------|
| [`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) | Prices an order without placing it, for every carrier or for one |
| [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) | Places an order |
| [`SystemServiceInterface::validateCouponCode()`](../src/Api/Service/SystemServiceInterface.php) | Checks a discount coupon, optionally against a specific order |

[`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) and
[`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) take the same [`CreateOrderMessage`](../src/Dto/Request/Order/CreateOrderMessage.php).

> A successful valuation only gives you a price. A payload that prices without errors can still be
> rejected when you place the order, so a valuation doesn't show whether an order will go through.

[`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) quotes every carrier that can carry the shipment, one
[`ValuationMessage`](../src/Dto/Response/Order/ValuationMessage.php) each, whichever courier the message names. A carrier that can't carry it is left out,
and if none can, the call fails with `EMPTY_VALUATION`. Given a carrier's service code as well, it
prices the order for that carrier only:

```php
$quotes = $client->orders()->orderValuation($order, 'DPD');   // ValuationMessage[], just DPD's quote
```

A code the API doesn't know is rejected. A single carrier's quote has no service name.
The net and gross prices are the totals to be charged, with any promotion and rebate already taken
off. A price only means something when [`ValuationMessage::$shipment`](../src/Dto/Response/Order/ValuationMessage.php) is true, and a conditional
price of 0 means no conditional offer, not a free one.

To find an order again by your own id, place it with `externalApplicationId` (your application's
UUID) and `externalOrderId` (your id for the order). The SDK refuses one without the other.
`externalOrderNumber` can carry the number your customer sees.

[`SystemServiceInterface::validateCouponCode()`](../src/Api/Service/SystemServiceInterface.php) checks a coupon on its own or against a specific order. Checking against an
order needs the carrier, the shipment type and the parcels. If any of them are missing, the SDK
reports all the missing ones together.

Full example: [Recipes: placing an order](recipes.md#placing-an-order).

Reading orders back
-------------------

| Method | What it does |
|--------|--------------|
| [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | Lists orders, one page at a time |
| [`OrdersServiceInterface::getStatus()`](../src/Api/Service/OrdersServiceInterface.php) | One order's current delivery status, optionally with its scan history |
| [`OrdersServiceInterface::cancelOrder()`](../src/Api/Service/OrdersServiceInterface.php) | Cancels an order |

```php
$list   = $client->orders()->getOrders($filter);        // OrderListMessage: paged, full Order objects
$status = $client->orders()->getStatus($statusMessage); // OrderStatusMessage: one order's delivery state
```

[`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) is the general query. It filters by date, status, carrier, cash-on-delivery range,
free text and your own external identifiers, and can optionally include `packs`, `items` and
`files`. The application id filters on its own. The order id only works together with it, which
the SDK checks before sending. Both match exactly, and no match is an empty page. This is how to
check whether an order was placed when [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) got no answer, see
[Errors: retrying safely](errors.md#retrying-safely). Results are paged (see [Recipes: paging](recipes.md#paging-through-a-list)). The maximum
page size is [`GetOrdersMessage::MAX_PAGE_SIZE`](../src/Dto/Request/Order/GetOrdersMessage.php) (100), and the SDK rejects anything larger before
sending it. Filtered by order number, it returns that order alone, with its parcels, items and
files, and an unknown number is an error.

[`OrdersServiceInterface::getStatus()`](../src/Api/Service/OrdersServiceInterface.php) returns the current delivery state of one order, optionally with its scan history.
It is the lighter call, suited to tracking. [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) suits reconciliation.
To hear about changes without asking, see [Webhooks](webhooks.md).

Both include a status code. [`Order::getStatusCode()`](../src/Entity/Order.php) returns a plain string, and
[`Enum\Order\OrderStatusCodeEnum`](../src/Enum/Order/OrderStatusCodeEnum.php) holds the known values:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($order->getStatusCode() === OrderStatusCodeEnum::Delivered->value) { … }
```

Labels and other documents
--------------------------

| Method | What it does |
|--------|--------------|
| [`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) | Shipping labels for one or more orders, as one document |
| [`OrdersServiceInterface::getProtocol()`](../src/Api/Service/OrdersServiceInterface.php) | The handover protocol, the list the courier signs on collection |
| [`OrdersServiceInterface::getOrderFile()`](../src/Api/Service/OrdersServiceInterface.php) | A document attached to an order |

```php
$labels   = $client->orders()->getLabel($doc);      // shipping labels, one PDF for all of them
$protocol = $client->orders()->getProtocol($doc);   // the handover list the courier signs
$file     = $client->orders()->getOrderFile($msg);  // a document attached to one order
```

[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) and [`OrdersServiceInterface::getProtocol()`](../src/Api/Service/OrdersServiceInterface.php) both take [`GetDocumentMessage`](../src/Dto/Request/Order/GetDocumentMessage.php), which accepts a **list** of order
numbers. You get labels for several orders back as one PDF from a single call.
[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) also accepts a [`LabelFileFormatEnum`](../src/Enum/Order/LabelFileFormatEnum.php) for formats other than PDF, such as ZPL, if the
carrier supports them, for a single order number only.

Every document, these and the invoices below alike, holds the file's bytes in `$file`, decoded from
the base64 the API sends:

```php
file_put_contents('labels.pdf', $labels->file);     // write it out
echo '<embed src="data:application/pdf;base64,' . \base64_encode($labels->file) . '">';  // hand it to a browser
```

Booking a collection
--------------------

| Method | What it does |
|--------|--------------|
| [`CourierServiceInterface::getCourierPickupTime()`](../src/Api/Service/CourierServiceInterface.php) | The days a carrier can collect on, with the windows available each day |
| [`CourierServiceInterface::pickupCourier()`](../src/Api/Service/CourierServiceInterface.php) | Whether a carrier can collect on one day, and in which windows. Books nothing |
| [`OrdersServiceInterface::isMultiPickupAvailable()`](../src/Api/Service/OrdersServiceInterface.php) | Checks whether a shipment can join a collective pickup |
| [`OrdersServiceInterface::createMultiOrderPickup()`](../src/Api/Service/OrdersServiceInterface.php) | Books one courier collection covering several orders |

A collection for one order is booked with the order, through
[`Order::setPickup()`](../src/Entity/Order.php). To offer only the days and
windows the carrier accepts, ask first:

```php
$times  = $client->courier()->getCourierPickupTime($whenMessage);  // available days and windows
$pickup = $client->courier()->pickupCourier($pickupMessage);       // one day
```

One collection for *several* shipments is a collective pickup. It is booked in three steps:

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

Availability depends on the shipment, chiefly its carrier and parcels, so it is checked for each
one. The API answers false rather than an error for a request it can't evaluate.

Addresses, bank accounts and parcel templates
---------------------------------------------

| Method | What it does |
|--------|--------------|
| [`AddressesServiceInterface::getAddresses()`](../src/Api/Service/AddressesServiceInterface.php) | Lists saved addresses |
| [`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) | Creates an address, or updates the one the message's id names |
| [`AddressesServiceInterface::deleteAddress()`](../src/Api/Service/AddressesServiceInterface.php) | Deletes one address book entry |
| [`BankAccountsServiceInterface::getBankAccounts()`](../src/Api/Service/BankAccountsServiceInterface.php) | Lists the registered bank accounts |
| [`BankAccountsServiceInterface::saveBankAccount()`](../src/Api/Service/BankAccountsServiceInterface.php) | Registers a bank account, or updates the one the message's id names |
| [`BankAccountsServiceInterface::deleteBankAccount()`](../src/Api/Service/BankAccountsServiceInterface.php) | Removes a bank account |
| [`PackTemplatesServiceInterface::getPackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | Lists the account's parcel templates |
| [`PackTemplatesServiceInterface::savePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | Creates a template, or updates the one its id names |
| [`PackTemplatesServiceInterface::deletePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | Deletes a template |
| [`GeoServiceInterface::findCity()`](../src/Api/Service/GeoServiceInterface.php) | Suggests localities for a postcode |

Save creates and updates: with an id in the message it updates that entry, and without one it
creates a new entry. An id that names no entry of the account fails an address save with
`NOT_FOUND`, creates a new parcel template, and makes a bank account save throw
[`MalformedResponseException`](../src/Exception/MalformedResponseException.php).

You send an [`Address`](../src/Entity/Address.php) and get the stored entry back as one, so an entry you read can be changed
and saved again. See [Messages: one class per record](messages.md#one-class-per-record).
[`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) and
[`PackTemplatesServiceInterface::savePackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) take the record itself.
[`BankAccountsServiceInterface::saveBankAccount()`](../src/Api/Service/BankAccountsServiceInterface.php) and
[`AccountServiceInterface::savePhoneNumber()`](../src/Api/Service/AccountServiceInterface.php) take a message wrapping it, which carries the
PIN or two-factor fields the API may require.

[`GeoServiceInterface::findCity()`](../src/Api/Service/GeoServiceInterface.php) returns the localities for a Polish postcode, e.g. for address
autocompletion.

A bank account number is checked before it is sent. The [`Validator\Rule\Iban`](../src/Validator/Rule/Iban.php) rule verifies the
ISO 13616 check digits, and accepts either a full IBAN or a national number without the country
prefix. The check is strict because this field decides where collected money is paid, and two
swapped digits could send it to someone else's account. The API accepts Polish accounts only.
Registering a bank account, or changing its number or default flag, needs the security PIN while
bank account editing is PIN-locked, and [two-factor confirmation](#two-factor-confirmation)
otherwise. A money box payout to a bank account must name a registered one.

Parcel templates are optional. An order always carries its parcel dimensions in [`PackItem`](../src/Entity/Order/PackItem.php).
Templates save repeating standard box sizes for every order.

The account
-----------

| Method | What it does |
|--------|--------------|
| [`AccountServiceInterface::getAccountInfo()`](../src/Api/Service/AccountServiceInterface.php) | The account's profile, balances and outstanding obligations |
| [`SystemServiceInterface::getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) | The service limits: coupons, security and tickets |
| [`AccountServiceInterface::saveAccountSettings()`](../src/Api/Service/AccountServiceInterface.php) | Changes the account's order form defaults |
| [`AccountServiceInterface::changeDefaultCourier()`](../src/Api/Service/AccountServiceInterface.php) | Sets or clears the carrier preselected on the order form |
| [`AccountServiceInterface::changeDefaultSenderPoint()`](../src/Api/Service/AccountServiceInterface.php) | Sets, or clears, the default drop-off point for one carrier |
| [`AccountServiceInterface::savePayerAddress()`](../src/Api/Service/AccountServiceInterface.php) | Writes the billing, correspondence or contact address, as [`PayerAddressTypeEnum`](../src/Enum/Account/PayerAddressTypeEnum.php) says |
| [`AccountServiceInterface::changePassword()`](../src/Api/Service/AccountServiceInterface.php) | Changes the account password |
| [`AccountServiceInterface::changePhonePin()`](../src/Api/Service/AccountServiceInterface.php) | Sets or changes the account's telephone PIN |
| [`AccountServiceInterface::validateUserPin()`](../src/Api/Service/AccountServiceInterface.php) | Checks a security PIN without changing anything |
| [`AccountServiceInterface::getRegulations()`](../src/Api/Service/AccountServiceInterface.php) | Fetches the terms of service or the privacy policy |
| [`AccountServiceInterface::acceptRegulations()`](../src/Api/Service/AccountServiceInterface.php) | Records the customer's acceptance of the current terms |
| [`AccountServiceInterface::getPhoneNumbers()`](../src/Api/Service/AccountServiceInterface.php) | Lists the account's telephone numbers |
| [`AccountServiceInterface::savePhoneNumber()`](../src/Api/Service/AccountServiceInterface.php) | Adds a telephone number, optionally as the account's default |
| [`AccountServiceInterface::deletePhoneNumber()`](../src/Api/Service/AccountServiceInterface.php) | Removes a telephone number from the account |
| [`AccountVerificationServiceInterface::register()`](../src/Api/Service/AccountVerificationServiceInterface.php) | Starts an identity verification, or returns the one in progress |
| [`AccountVerificationServiceInterface::getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php) | The most recent verification attempt and its outcome, or null |

[`AccountServiceInterface::getAccountInfo()`](../src/Api/Service/AccountServiceInterface.php) returns the balances, outstanding payments and PIN lock settings, which
decide whether an operation takes the security PIN or a two-factor code. [`SystemServiceInterface::getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) returns the coupon,
security and ticket limits. The SDK checks none of this data locally, because it can change without
a new SDK release.

> [`AccountServiceInterface::acceptRegulations()`](../src/Api/Service/AccountServiceInterface.php) records that the customer accepts the current terms, which is a legal act on
> their behalf. Called at start-up, it would accept the terms for a customer who never saw them.

An account whose [`AccountInfoMessage::$requireAgreementVerification`](../src/Dto/Response/Account/AccountInfoMessage.php) is true has to verify its identity.
[`AccountVerificationServiceInterface::register()`](../src/Api/Service/AccountVerificationServiceInterface.php) starts it, or returns the one in progress, with its access
token; the customer finishes with the provider.
[`AccountVerificationServiceInterface::getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php) returns the latest attempt and its result, or `null` if none has
been started.

Invoices, payments and payouts
------------------------------

| Method | What it does |
|--------|--------------|
| [`InvoicesServiceInterface::getInvoices()`](../src/Api/Service/InvoicesServiceInterface.php) | Lists invoices, one page at a time |
| [`InvoicesServiceInterface::getInvoicePdf()`](../src/Api/Service/InvoicesServiceInterface.php) | One invoice or correction as a PDF |
| [`InvoicesServiceInterface::getInvoiceSummaryList()`](../src/Api/Service/InvoicesServiceInterface.php) | The itemised summary behind one invoice, as a PDF or a CSV file |
| [`PaymentsServiceInterface::registerPayment()`](../src/Api/Service/PaymentsServiceInterface.php) | Registers an online payment and returns the token needed to complete it |
| [`PaymentsServiceInterface::getPaymentInfo()`](../src/Api/Service/PaymentsServiceInterface.php) | The current state of a registered payment |
| [`PaymentsServiceInterface::getPrepaidHistory()`](../src/Api/Service/PaymentsServiceInterface.php) | Movements on the prepaid balance, one page at a time |
| [`MoneyBoxServiceInterface::getConfig()`](../src/Api/Service/MoneyBoxServiceInterface.php) | The account's payout settings |
| [`MoneyBoxServiceInterface::calculatePayoutFee()`](../src/Api/Service/MoneyBoxServiceInterface.php) | What a payout would cost, without requesting one |
| [`MoneyBoxServiceInterface::createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) | Requests a payout of collected cash on delivery |
| [`MoneyBoxServiceInterface::cancelPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) | Cancels a payout while it is still cancellable |
| [`MoneyBoxServiceInterface::getPayouts()`](../src/Api/Service/MoneyBoxServiceInterface.php) | Lists payout requests, one page at a time |

An invoice's itemised summary lists the shipments it covers, for reconciling line by line, as a
PDF or a CSV file ([`InvoiceSummaryFormatEnum`](../src/Enum/Invoice/InvoiceSummaryFormatEnum.php)). The summary and the invoice PDF both take the invoice
number and return base64, like order documents.

[`PaymentsServiceInterface::registerPayment()`](../src/Api/Service/PaymentsServiceInterface.php) takes a [`PaymentModeEnum`](../src/Enum/Payment/PaymentModeEnum.php) that says what the payment is for. Send an amount
only for top-ups ([`PaymentModeEnum::Prepaid`](../src/Enum/Payment/PaymentModeEnum.php)). A top-up needs an amount greater than zero, and the
SDK checks this before sending. [`PaymentModeEnum::Invoices`](../src/Enum/Payment/PaymentModeEnum.php) and
[`PaymentModeEnum::Orders`](../src/Enum/Payment/PaymentModeEnum.php) need their list of numbers; an order payment spends the prepaid balance
first. [`PaymentModeEnum::Verification`](../src/Enum/Payment/PaymentModeEnum.php) fails with UNKNOWN_ERROR. [Webhooks](webhooks.md) don't cover
payments; [`PaymentsServiceInterface::getPaymentInfo()`](../src/Api/Service/PaymentsServiceInterface.php) reads their state.

Cash collected on delivery is held on the account until it is paid out:

```
moneyBox()->getConfig()             the payout settings
        │
moneyBox()->calculatePayoutFee()    what it would cost, nothing requested yet
        │
moneyBox()->createPayout()          the request; may need a PIN or two-factor confirmation
        │
moneyBox()->getPayouts()            whether it went through
        │
        └── moneyBox()->cancelPayout()   while the payout is cancellable
```

The payout fee depends on the payout type ([`MoneyBoxPayoutTypeEnum`](../src/Enum/MoneyBox/MoneyBoxPayoutTypeEnum.php)) and on whether the account has free payouts
left this month.

> [`MoneyBoxServiceInterface::createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) moves real money. A postal money order to an address other than the saved
> default needs a security PIN or [two-factor confirmation](#two-factor-confirmation). [`MoneyBoxServiceInterface::cancelPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) works only
> while [`MoneyBoxPayoutMessage::$cancellable`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutMessage.php) is true.

Full example: [Recipes: paying out collected money](recipes.md#paying-out-collected-money).

Two-factor confirmation
-----------------------

| Method | What it does |
|--------|--------------|
| [`TwoFactorTokenServiceInterface::twoFactorTokenRegister()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Starts a confirmation and sends a code to the customer |
| [`TwoFactorTokenServiceInterface::twoFactorTokenResend()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Sends the code again on the same channel |
| [`TwoFactorTokenServiceInterface::twoFactorTokenVerify()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Checks the code the customer entered |
| [`TwoFactorTokenServiceInterface::twoFactorTokenChangeTransportMethod()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Switches a pending confirmation to another channel |

```
twoFactorTokenRegister(operation)        a code goes to the customer
        │
        ├── the code didn't arrive?
        │     ├── twoFactorTokenResend()                     again, on the same channel
        │     └── twoFactorTokenChangeTransportMethod()      on another channel
        │
the customer enters the code
        ├── twoFactorTokenVerify(token, code)                only checks it
        └── the operation itself, with token and code        e.g. moneyBox()->createPayout()
```

The operation is a [`TwoFactorNamespaceEnum`](../src/Enum/TwoFactorToken/TwoFactorNamespaceEnum.php) value.

The [`TwoFactorToken`](../src/Dto/Response/TwoFactorToken.php) says how the code was sent: [`TwoFactorToken::$transportMethod`](../src/Dto/Response/TwoFactorToken.php) names where it
went, e.g. a masked phone number. [`TwoFactorToken::$availableMethods`](../src/Dto/Response/TwoFactorToken.php) lists the channels, the current one
included, by the id that switching to one takes.

Full example: [Recipes: two-factor confirmation](recipes.md#two-factor-confirmation).

Support and help
----------------

| Method | What it does |
|--------|--------------|
| [`TicketsServiceInterface::getContactTopics()`](../src/Api/Service/TicketsServiceInterface.php) | Lists the topics a ticket or a contact form can be opened under |
| [`TicketsServiceInterface::createTicket()`](../src/Api/Service/TicketsServiceInterface.php) | Raises a support ticket |
| [`TicketsServiceInterface::getTickets()`](../src/Api/Service/TicketsServiceInterface.php) | Lists tickets, one page at a time |
| [`TicketsServiceInterface::getTicketMessages()`](../src/Api/Service/TicketsServiceInterface.php) | A ticket's conversation, newest entry first |
| [`TicketsServiceInterface::createTicketMessage()`](../src/Api/Service/TicketsServiceInterface.php) | Adds a reply to a ticket |
| [`TicketsServiceInterface::setTicketStatus()`](../src/Api/Service/TicketsServiceInterface.php) | Changes a ticket's status |
| [`TicketsServiceInterface::getTicketMessageFile()`](../src/Api/Service/TicketsServiceInterface.php) | Downloads one attachment from a ticket conversation |
| [`TicketsServiceInterface::getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) | Fetches a contact form |
| [`TicketsServiceInterface::saveContactForm()`](../src/Api/Service/TicketsServiceInterface.php) | Submits a completed contact form |
| [`KnowledgeBaseServiceInterface::getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | All help categories and articles as a flat list |
| [`KnowledgeBaseServiceInterface::search()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | Searches the help articles by free text |
| [`KnowledgeBaseServiceInterface::getArticle()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | One article with its body and attachments |

```
Ticket: a conversation                     Contact form: a submission

getContactTopics()                         getContactTopics()
      │                                          │  a topic that has a form
createTicket(topic id, content, files)     getContactForm(topic id)
      │                                          │  fields, form id and nonce
getTickets() ─► getTicketMessages(id)      saveContactForm(topic id, form id, nonce, answers)
      │              │
      │         getTicketMessageFile()
      │
createTicketMessage(id, content, files)
setTicketStatus(id, status)
```

A contact form's fields are defined by the API. Depending on the topic, a submission may open a
ticket, which then shows in [`TicketsServiceInterface::getTickets()`](../src/Api/Service/TicketsServiceInterface.php).

The calls that take a ticket take its id, not its number. The conversation is a list of
[`TicketTimelineItemMessage`](../src/Dto/Response/Ticket/TicketTimelineItemMessage.php) entries, newest first: messages, and actions such as closing the ticket. A reply
returns the new entry. Ticket lists are paged, up to
[`GetTicketsMessage::MAX_PAGE_SIZE`](../src/Dto/Request/Ticket/GetTicketsMessage.php) (250) per page.

Attachments are [`TicketAttachmentMessage`](../src/Dto/Request/Ticket/TicketAttachmentMessage.php) entries: a file name with an accepted extension,
its media type and its contents in base64. A message carries up to 5. Each is stored under a new
unique name, given by the timeline; [`TicketsServiceInterface::getTicketMessageFile()`](../src/Api/Service/TicketsServiceInterface.php) needs that name.

When [`TicketMessage::$responseTemplate`](../src/Dto/Response/Ticket/TicketMessage.php) is set, a reply must be one of its answers.

A contact form's category is the id of a topic that has a form, from
[`TicketsServiceInterface::getContactTopics()`](../src/Api/Service/TicketsServiceInterface.php). For a website-only form, [`ContactFormMessage::$webOnly`](../src/Dto/Response/Contact/ContactFormMessage.php) is true and
[`ContactFormMessage::$href`](../src/Dto/Response/Contact/ContactFormMessage.php) gives its page; the API takes no submission. A submission quotes the
form id and nonce of one fetch, and the nonce works once, within an hour. The answers go in
[`SaveContactFormMessage`](../src/Dto/Request/Contact/SaveContactFormMessage.php)'s `fields`, keyed by the names of the form's fields.

The form is a [`ContactFormMessage`](../src/Dto/Response/Contact/ContactFormMessage.php) with a list of [`ContactFormFieldMessage`](../src/Dto/Response/Contact/ContactFormFieldMessage.php) entries. Besides
inputs, the list has elements that take no answer, such as headings, notices and the submit button.
A field may carry a condition saying when it's shown. The SDK gives it as text, as the form states
it.

[`TicketsServiceInterface::saveContactForm()`](../src/Api/Service/TicketsServiceInterface.php) returns a [`ContactFormResultMessage`](../src/Dto/Response/Contact/ContactFormResultMessage.php): the message to show, how to show it,
and the form's data, such as the number of the ticket it opened. A form that needs the code sent by
e-mail answers with the [`ContactFormResultStatusEnum::Error`](../src/Enum/Contact/ContactFormResultStatusEnum.php) status and a new nonce: show its
auth code field and submit again.

For help articles, you can fetch the flat index of all categories and articles with
[`KnowledgeBaseServiceInterface::getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) and filter it yourself, or use
[`KnowledgeBaseServiceInterface::search()`](../src/Api/Service/KnowledgeBaseServiceInterface.php). The index changes rarely, so you can cache it. Search works better for
search-as-you-type.

Checking the connection and credentials
---------------------------------------

| Method | What it does |
|--------|--------------|
| [`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) | Confirms the API is reachable and reports its clock, without credentials |
| [`UtilsServiceInterface::testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) | Verifies a set of credentials without doing anything else |

[`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) sends no credentials, which suits health checks. It tells
you whether the API is reachable and returns the server time, whether or not your token is valid.

[`UtilsServiceInterface::testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) takes its own [`AuthorizationMessage`](../src/Dto/Request/AuthorizationMessage.php) instead of the
configured one. You can use it to check credentials before saving them, e.g. in a settings form.

```php
try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $typed, login: $id));
    // good
} catch (AuthorizationException) {
    // not good, and nothing was saved
}
```

Reading webhook events
----------------------

| Method | What it does |
|--------|--------------|
| [`OrderReaderInterface::isAuthentic()`](../src/Api/Webhook/OrderReaderInterface.php) | Checks an event against the webhook's secret |
| [`OrderReaderInterface::read()`](../src/Api/Webhook/OrderReaderInterface.php) | Reads the event into an [`OrderEventMessage`](../src/Dto/Response/Webhook/OrderEventMessage.php) |
| [`OrderReaderInterface::getLastDrift()`](../src/Api/Webhook/OrderReaderInterface.php) | How the last event's order differed from the SDK |

[`PolkurierWebservice::orderReader()`](../src/PolkurierWebservice.php) returns the reader as
[`Api\Webhook\OrderReaderInterface`](../src/Api/Webhook/OrderReaderInterface.php), built on first use with the [`SerializerConfig`](../src/Serializer/SerializerConfig.php) the
client was given and kept after that, or the one passed under [`OrderReader`](../src/Webhook/OrderReader.php) in `services:`. It reads the
request body and calls no endpoint.
[Webhooks](webhooks.md) covers the exchange.
