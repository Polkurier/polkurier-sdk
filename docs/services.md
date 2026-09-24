Services
========

The client exposes one service per functional area, and one method per API endpoint. Every
accessor returns an interface from `Polkurier\Webservice\Api\Service\` — type-hint that rather than
the concrete class, so a substituted implementation still satisfies your code.

| Accessor | Interface | Covers |
|----------|-----------|--------|
| [`courier()`](#courier) | `CourierServiceInterface` | Carriers, collections, pickup points, country coverage |
| [`orders()`](#orders) | `OrdersServiceInterface` | Pricing, placing, tracking, cancelling, documents |
| [`addresses()`](#addresses) | `AddressesServiceInterface` | The account's address book |
| [`bankAccounts()`](#bankaccounts) | `BankAccountsServiceInterface` | Accounts cash on delivery is settled to |
| [`packTemplates()`](#packtemplates) | `PackTemplatesServiceInterface` | Saved parcel templates |
| [`geo()`](#geo) | `GeoServiceInterface` | City lookup and map tokens |
| [`account()`](#account) | `AccountServiceInterface` | Profile, balances, preferences, credentials, phone numbers |
| [`accountVerification()`](#accountverification) | `AccountVerificationServiceInterface` | Identity verification |
| [`invoices()`](#invoices) | `InvoicesServiceInterface` | Invoices and their documents |
| [`payments()`](#payments) | `PaymentsServiceInterface` | Online payments and the prepaid balance |
| [`moneyBox()`](#moneybox) | `MoneyBoxServiceInterface` | Collected funds and withdrawing them |
| [`tickets()`](#tickets) | `TicketsServiceInterface` | Support tickets and contact forms |
| [`knowledgeBase()`](#knowledgebase) | `KnowledgeBaseServiceInterface` | Help articles |
| [`twoFactorToken()`](#twofactortoken) | `TwoFactorTokenServiceInterface` | Confirmation codes for guarded operations |
| [`system()`](#system) | `SystemServiceInterface` | Service limits, carrier notices, coupon checks |
| [`utils()`](#utils) | `UtilsServiceInterface` | Reachability and credential checks |

Reading a method signature
--------------------------

Three patterns recur:

```php
availableCarriers(?AvailableCarriersMessage $message = null): array   // all filters optional
createOrder(CreateOrderMessage $message): CreatedOrderMessage         // payload required
getBankAccounts(): array                                             // no parameters at all
```

Return types are what they say. A method promising a `CreatedOrderMessage` either returns one or
throws; there is no null for "the API sent something odd". The exception to that is
`getCourierPoint()`, which is genuinely nullable: an id that matches nothing is an absence rather
than a failure, and comes back as null. The two methods that work this way are marked below.

Every method can throw `AuthorizationException`, `ConnectionException`,
`MalformedResponseException`, `ValidationException` and `WebserviceException`. That is not repeated
per method; see [Errors](errors.md).

The docblock on each interface method documents the endpoint it calls, a sample payload, the
success responses including their variants, and the errors most easily hit. Those interfaces double
as the API reference — this page is the map, they are the detail.

---

courier()
---------

`Api\Service\CourierServiceInterface` — carriers, collections, pickup points, country coverage.

| Method | Returns |
|--------|---------|
| `availableCarriers(?AvailableCarriersMessage)` | `Carrier[]` |
| `pickupCourier(PickupMessage)` | `PickupResponseMessage` |
| `getCourierPickupTime(GetPickupTimeMessage)` | `PickupTimeMessage[]` |
| `getCountries(GetCountriesMessage)` | `array<string, string>` |
| `getSupportedCountries()` | `Country[]` |
| `findCourierPoints(GetPointMessage)` | `CourierPoint[]` |
| `getCourierPoint(string $id, array $couriers)` | `CourierPoint\|null` |

`availableCarriers()` is where an integration starts. The `serviceCode` of each `Carrier` is what
goes into `CreateOrderMessage::$courier`, and with `additionalData: true` the response also carries
what that carrier supports — shipment types, pack limits, whether it needs a pickup date, which
additional services it sells, which point map it uses. The SDK deliberately does not validate
against any of that, because it changes per carrier and per account; read it from here instead.

```php
$carriers = $client->courier()->availableCarriers(
    new AvailableCarriersMessage(additionalData: true),
);
```

### Finding a pickup point — three ways

This is the clearest case of the API offering the same thing through several doors, and the SDK
keeps all of them because they answer different questions.

**1. Search across carriers.** The general-purpose one: a query, a location, a filter.

```php
use Polkurier\Webservice\Request\Courier\GetPointMessage;

$points = $client->courier()->findCourierPoints(new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
));
```

**2. Look one up by id.** When the customer has already chosen, and you are re-reading the point to
confirm it still exists:

```php
$point = $client->courier()->getCourierPoint('POZ01A', ['INPOST_PACZKOMAT']);

if ($point === null) {
    // The id is unknown to that carrier. This is an absence, not an error.
}
```

The carrier list is required even though the id looks unique: the lookup is scoped to a carrier,
so the signature takes both.

They are large responses — cache them rather than calling per checkout.

**4. Hand the customer a map.** `$client->geo()->getMapToken()` issues a short-lived token for the
pickup point map widget, which then reports the chosen point back to your page. Use that when the
customer is choosing, and one of the three above when your code is.

### Collections

```php
$pickup = $client->courier()->pickupCourier($pickupMessage);       // one shipment
$times  = $client->courier()->getCourierPickupTime($whenMessage);  // available days and windows
```

Booking a collection covering *several* shipments at once is
[`orders()->createMultiOrderPickup()`](#orders) instead, gated by
`orders()->isMultiPickupAvailable()`.

### Country coverage — two methods, two questions

`getSupportedCountries()` answers "what does Polkurier serve?" and returns `Country[]` with their
regions. `getCountries(GetCountriesMessage)` answers "where does *this* carrier deliver?" and
returns a flat code-to-name map. The first is for building a form; the second for validating
against one carrier.

---

orders()
--------

`Api\Service\OrdersServiceInterface` — the core of the SDK.

| Method | Returns |
|--------|---------|
| `orderValuation(CreateOrderMessage)` | `array<array-key, mixed>` |
| `createOrder(CreateOrderMessage)` | `CreatedOrderMessage` |
| `getOrders(?GetOrdersMessage)` | `OrderListMessage` |
| `cancelOrder(OrderIdMessage)` | `CancellationMessage` |
| `getLabel(GetDocumentMessage)` | `DocumentMessage` |
| `getProtocol(GetDocumentMessage)` | `DocumentMessage` |
| `getStatus(GetStatusMessage)` | `OrderStatusMessage` |
| `isMultiPickupAvailable()` | `MultiPickupAvailabilityMessage` |
| `createMultiOrderPickup(MultiPickupRequestMessage)` | `MultiPickupResultMessage` |
| `getOrderFile(GetOrderFileMessage)` | `OrderFileMessage` |

### Pricing and placing

`orderValuation()` and `createOrder()` take the same `CreateOrderMessage`. Sharing the payload
does not mean sharing the outcome.

> Treat a successful quote as a price, not as a dry run. A payload that prices cleanly can still be
> refused when you go on to place it, so do not use valuation to check whether an order is
> placeable.

`orderValuation()` returns a decoded array rather than a mapped object — it is one of the two
endpoints whose answer has no stable structure to map onto, so the SDK hands back what arrived.
See [Messages: unmapped responses](messages.md#the-two-unmapped-responses).

Full worked example: [Recipes — placing an order](recipes.md#placing-an-order).

### Reading orders back — two methods, and they are not interchangeable

```php
$list   = $client->orders()->getOrders($filter);        // OrderListMessage: paged, full Order objects
$status = $client->orders()->getStatus($statusMessage); // OrderStatusMessage: one order's delivery state
```

`getOrders()` is the general query: filters on date, status, carrier, cash-on-delivery range, free
text, and your own external identifiers, with `packs`, `items` and `files` as opt-in extras. It
pages — see [Recipes — paging](recipes.md#paging-through-a-list). Page size is capped at
`GetOrdersMessage::MAX_PAGE_SIZE` (100), and the SDK rejects a larger one rather than sending a
value that will not be honoured in full.

`getStatus()` is the cheap one: current delivery state of a single order, optionally with its scan
history. Use it for tracking; use `getOrders()` for reconciliation.

Both carry a status code. `Order::getStatusCode()` is a plain string, and
`Enum\Order\OrderStatusCodeEnum` holds the values the API is known to send — compare against the
enum rather than against string literals:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($order->getStatusCode() === OrderStatusCodeEnum::Delivered->value) { … }
```

### Documents — three methods, three documents

```php
$labels   = $client->orders()->getLabel($doc);      // shipping labels, one PDF for all of them
$protocol = $client->orders()->getProtocol($doc);   // the handover list the courier signs
$file     = $client->orders()->getOrderFile($msg);  // a document attached to one order
```

`getLabel()` and `getProtocol()` share `GetDocumentMessage`, which takes a **list** of order
numbers — several labels come back as one PDF rather than several calls.

Each returns base64 in `getFile()` and the bytes in `getDecodedFile()`. Two ways to use it:

```php
file_put_contents('labels.pdf', $labels->getDecodedFile());     // write it out
echo '<embed src="data:application/pdf;base64,' . $labels->getFile() . '">';  // hand it to a browser
```

`getLabel()` also accepts a `LabelFileFormatEnum` for printer languages other than PDF — ZPL and
the like — where the carrier supports it.

### Collections for several orders

```php
if ($client->orders()->isMultiPickupAvailable()->isAvailable()) {
    $result = $client->orders()->createMultiOrderPickup($request);
}
```

Not every account may do this, which is what the availability call is for. For a single shipment,
use [`courier()->pickupCourier()`](#courier).

addresses()
-----------

`Api\Service\AddressesServiceInterface` — the account's address book.

| Method | Returns |
|--------|---------|
| `getAddresses(?Address\GetMessage)` | `Address[]` |
| `saveAddress(Address\SaveMessage)` | `Address` |
| `deleteAddress(Address\DeleteMessage)` | `bool` |

`saveAddress()` both creates and updates: give the message an id and it updates that entry, leave
it out and it creates one. That is one method rather than two because the API has one endpoint —
the same pattern holds for bank accounts and pack templates.

Note the asymmetry that runs through the whole SDK: you write a `Request\Address\SaveMessage` and
read a `Response\Address`. They are different classes describing different things — see
[Messages: why requests and responses are split](messages.md#why-requests-and-responses-are-split).

---

bankAccounts()
--------------

`Api\Service\BankAccountsServiceInterface` — where cash on delivery is settled to.

| Method | Returns |
|--------|---------|
| `getBankAccounts()` | `BankAccount[]` |
| `saveBankAccount(BankAccount\SaveMessage)` | `BankAccount` |
| `deleteBankAccount(DeleteBankAccountMessage)` | `bool` |

The account number is checked locally before it is sent: `Validate\Iban` verifies the ISO 13616
check digits rather than counting characters, accepting either a full IBAN or a bare national
number. This field decides where collected money goes, and a transposed pair of digits is a payment
to a stranger — so it is one of the few places the SDK is deliberately strict.

Changing a bank account is one of the operations that may require a
[two-factor token](#twofactortoken) depending on the account's settings.

---

packTemplates()
---------------

`Api\Service\PackTemplatesServiceInterface` — saved parcel templates.

| Method | Returns |
|--------|---------|
| `getPackTemplates()` | `PackTemplate[]` |
| `savePackTemplates(PackTemplate\SaveMessage)` | `PackTemplate` |
| `deletePackTemplates(Common\DeleteMessage)` | `bool` |

Templates are a convenience on Polkurier's side, not a requirement: an order carries its parcel
dimensions in `PackMessage` whether or not a template exists. Use them when your own catalogue has
standard box sizes you would otherwise restate per order.

---

geo()
-----

`Api\Service\GeoServiceInterface` — city lookup and map tokens.

| Method | Returns |
|--------|---------|
| `findCity(FindCityMessage)` | `CityMessage[]` |
| `getMapToken()` | `TokenMessage` |

`findCity()` suggests localities for a postcode, which is what you want behind an autocompleting
address form: the customer types `61-001` and you offer what the API says is there, rather than
letting them type a city the carrier will not match.

`getMapToken()` issues a short-lived token for the pickup point map widget. The token is for the
browser; the point it comes back with goes into an order's address.

---

account()
---------

`Api\Service\AccountServiceInterface` — the largest surface, and the one most integrations touch
least.

| Method | Returns |
|--------|---------|
| `getAccountInfo(?GetAccountInfoMessage)` | `AccountInfoMessage` |
| `saveAccountSettings(SaveAccountSettingsMessage)` | `AccountInfoMessage` |
| `changeDefaultCourier(ChangeDefaultCourierMessage)` | `bool` |
| `changeDefaultSenderPoint(ChangeDefaultSenderPointMessage)` | `bool` |
| `savePayerAddress(SavePayerAddressMessage)` | `bool` |
| `changePassword(ChangePasswordMessage)` | `bool` |
| `changePhonePin(ChangePinMessage)` | `bool` |
| `validateUserPin(ValidatePinMessage)` | `PinValidityMessage` |
| `getRegulations(?GetRegulationsMessage)` | `RegulationsMessage` |
| `acceptRegulations(AcceptRegulationsMessage)` | `bool` |
| `getPhoneNumbers()` | `PhoneNumberMessage[]` |
| `savePhoneNumber(SavePhoneNumberMessage)` | `PhoneNumberMessage` |
| `deletePhoneNumber(DeletePhoneNumberMessage)` | `bool` |

`getAccountInfo()` is worth calling once at start-up even if you change nothing: it carries the
balances, the outstanding obligations and the PIN lock settings that decide whether other calls
will need confirming. Along with `system()->getSystemInfo()` and `courier()->availableCarriers()`,
it is the account-specific knowledge the SDK's local validation deliberately does not encode.

`savePayerAddress()` writes one of three distinct addresses — billing, correspondence, contact —
selected by `Enum\Account\PayerAddressTypeEnum`.

> `acceptRegulations()` records the customer's acceptance of the current terms. That is a legal act
> on their behalf; call it in response to something they did, not as part of your own start-up.

---

accountVerification()
---------------------

`Api\Service\AccountVerificationServiceInterface` — identity verification, where the account
requires it before shipping.

| Method | Returns |
|--------|---------|
| `register(?RegisterAccountVerificationMessage)` | `AccountVerificationMessage` |
| `getInfo()` | `AccountVerificationMessage` |

`register()` starts a verification and returns the token needed to complete it — the customer
finishes the process elsewhere. `getInfo()` reports the most recent attempt and its outcome; poll
that rather than assuming.

---

invoices()
----------

`Api\Service\InvoicesServiceInterface`.

| Method | Returns |
|--------|---------|
| `getInvoices(GetInvoicesMessage)` | `InvoiceListMessage` |
| `getInvoicePdf(GetInvoiceDocumentMessage)` | `InvoiceDocumentMessage` |
| `getInvoiceSummaryList(GetInvoiceDocumentMessage)` | `InvoiceDocumentMessage` |

Two ways to read one invoice: `getInvoicePdf()` for the document itself, and
`getInvoiceSummaryList()` for the itemised summary behind it — which is the one you want when
reconciling line by line rather than filing a PDF. Both take the same message and both answer with
base64, same as order documents.

---

payments()
----------

`Api\Service\PaymentsServiceInterface` — online payments and the prepaid balance.

| Method | Returns |
|--------|---------|
| `registerPayment(RegisterPaymentMessage)` | `RegisteredPaymentMessage` |
| `getPaymentInfo(GetPaymentInfoMessage)` | `PaymentInfoMessage` |
| `getPrepaidHistory(?GetPrepaidHistoryMessage)` | `PrepaidHistoryMessage` |

`registerPayment()` takes a `PaymentModeEnum` saying what is being paid for. Only one mode — topping
up the prepaid balance — takes an amount from the caller; for the others the amount is already
determined by what is being settled. The SDK enforces that distinction locally, so a payload with
the wrong combination is caught before it is sent.

`getPaymentInfo()` reports where a registered payment got to. It is a poll, not a webhook.

---

moneyBox()
----------

`Api\Service\MoneyBoxServiceInterface` — cash-on-delivery funds held on the account, and getting
them out.

| Method | Returns |
|--------|---------|
| `getConfig()` | `MoneyBoxConfigMessage` |
| `calculatePayoutFee(?CalculateMoneyBoxPayoutFeeMessage)` | `MoneyBoxFeeMessage` |
| `createPayout(CreateMoneyBoxPayoutMessage)` | `MoneyBoxPayoutMessage` |
| `cancelPayout(CancelMoneyBoxPayoutMessage)` | `MoneyBoxPayoutMessage` |
| `getPayouts(?GetMoneyBoxPayoutsMessage)` | `MoneyBoxPayoutListMessage` |

`calculatePayoutFee()` quotes what a payout would cost without requesting one — the equivalent of
`orderValuation()` for money. Call it first; the fee depends on the payout type and on who is
paying it (`MoneyBoxFeePaymentTypeEnum`).

> `createPayout()` moves real money. Depending on the account's settings it may need a security PIN,
> a [two-factor token](#twofactortoken), or both. `cancelPayout()` works only while the payout has
> not yet been sent.

---

tickets()
---------

`Api\Service\TicketsServiceInterface` — support tickets and the contact forms that feed them.

| Method | Returns |
|--------|---------|
| `getContactTopics()` | `ContactTopicMessage[]` |
| `createTicket(CreateTicketMessage)` | `TicketMessage` |
| `getTickets(?GetTicketsMessage)` | `TicketListMessage` |
| `getTicketMessages(TicketIdMessage)` | `TicketMessage` |
| `createTicketMessage(CreateTicketMessageMessage)` | `TicketMessage` |
| `setTicketStatus(SetTicketStatusMessage)` | `TicketMessage` |
| `getTicketMessageFile(GetTicketMessageFileMessage)` | `TicketFileContentMessage` |
| `getContactForm(GetContactFormMessage)` | `array<array-key, mixed>` |
| `saveContactForm(SaveContactFormMessage)` | `bool` |

Two routes into support, and they are for different things. A **ticket** is a conversation you own
and can poll: raise it with `createTicket()`, read it with `getTicketMessages()`, reply with
`createTicketMessage()`. A **contact form** is a one-way submission whose fields the API defines —
`getContactForm()` returns that definition and `saveContactForm()` submits it. Use a ticket when
you want the answer back in your own system.

`getContactForm()` is the second of the two endpoints whose shape varies with what was asked for,
so it returns a decoded array.

`GetTicketsMessage::MAX_PAGE_SIZE` is 250, higher than orders.

---

knowledgeBase()
---------------

`Api\Service\KnowledgeBaseServiceInterface` — the customer-facing help content.

| Method | Returns |
|--------|---------|
| `getIndex()` | `KnowledgeBaseEntryMessage[]` |
| `search(SearchKnowledgeBaseMessage)` | `KnowledgeBaseEntryMessage[]` |
| `getArticle(GetKnowledgeBaseArticleMessage)` | `KnowledgeBaseArticleMessage` |

Two ways to find an article: pull the whole flat index of categories and articles and filter it
yourself, or ask the API to search. The index is stable enough to cache; the search is better when
the customer is typing.

---

twoFactorToken()
----------------

`Api\Service\TwoFactorTokenServiceInterface` — confirmation codes for operations the account
guards.

| Method | Returns |
|--------|---------|
| `twoFactorTokenRegister(RegisterMessage)` | `TwoFactorToken` |
| `twoFactorTokenResend(Common\TokenMessage)` | `TwoFactorToken` |
| `twoFactorTokenVerify(VerifyMessage)` | `ValidityMessage` |
| `twoFactorTokenChangeTransportMethod(ChangeTransportMethodMessage)` | `TwoFactorToken` |

The flow is: register against a `TwoFactorNamespaceEnum` naming the operation, the customer
receives a code, you either verify it here or pass the token and code into the guarded call
itself. Two recovery paths exist if the code does not arrive — `twoFactorTokenResend()` to send it
again on the same channel, `twoFactorTokenChangeTransportMethod()` to switch channel.

Worked example: [Recipes — two-factor confirmation](recipes.md#two-factor-confirmation).

---

system()
--------

`Api\Service\SystemServiceInterface`.

| Method | Returns |
|--------|---------|
| `getSystemInfo()` | `SystemInfoMessage` |
| `getCouriersMessages()` | `CourierMessage[]` |
| `validateCouponCode(ValidateCouponMessage)` | `CouponValidationMessage` |

`getSystemInfo()` carries the limits a client should validate against — coupon limits, security
limits, ticket limits. The SDK hard-codes none of them, for the same reason it hard-codes no
carrier rules: they change without a release of this SDK, so read them rather than assuming them.

`getCouriersMessages()` is the notice board — delays, suspensions, general information, each with a
`CourierMessageSeverityEnum`. Surface these to whoever is placing orders; they explain a lot of
otherwise mysterious rejections.

`validateCouponCode()` checks a coupon either on its own or against a specific order. Against an
order means all three of carrier, shipment type and parcels, and the SDK reports all three at once
if any are missing — fixing them one at a time would be three round trips to learn one thing.

---

utils()
-------

`Api\Service\UtilsServiceInterface` — reachability and credential checks.

| Method | Returns |
|--------|---------|
| `heartbeat()` | `HeartbeatMessage` |
| `testAuthApi(AuthorizationMessage)` | `AuthConfirmationMessage` |

`heartbeat()` is the only method that sends no credentials, which makes it the right probe for a
health check: it answers whether the API is reachable and reports its clock, whatever state your
token is in.

`testAuthApi()` takes an explicit `AuthorizationMessage` rather than using the configured one, so
it can verify credentials you are about to save — a settings form testing what the user just typed,
before writing it anywhere.

```php
try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $typed, login: $id));
    // good
} catch (AuthorizationException) {
    // not good — and nothing was saved
}
```
