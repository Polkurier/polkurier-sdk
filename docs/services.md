Services
========

The client has one service per functional area and one method per API endpoint. Each accessor
returns an interface from `Polkurier\Webservice\Api\Service\`. Type-hint the interface, so your code
also accepts a replacement implementation.

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

Method signatures follow three patterns:

```php
availableCarriers(?AvailableCarriersMessage $message = null): array   // all filters optional
createOrder(CreateOrderMessage $message): CreatedOrderMessage         // payload required
getBankAccounts(): array                                             // no parameters at all
```

A method that returns a `CreatedOrderMessage` either returns one or throws. It never returns null
because the API sent something unexpected. The one nullable method is `getCourierPoint()`: an id
that matches no point returns null. It is marked in the table below.

Every method can throw `AuthorizationException`, `ConnectionException`,
`MalformedResponseException`, `ValidationException` and `WebserviceException`. This page doesn't
repeat that for each method. See [Errors](errors.md).

Each interface method's docblock lists the endpoint it calls, a sample payload, the possible
success responses and the most common errors. This page is an overview. The interfaces have the
details.

---

courier()
---------

`Api\Service\CourierServiceInterface`: carriers, collections, pickup points, country coverage.

| Method | Returns |
|--------|---------|
| `availableCarriers(?AvailableCarriersMessage)` | `Carrier[]` |
| `pickupCourier(PickupMessage)` | `PickupResponseMessage` |
| `getCourierPickupTime(GetPickupTimeMessage)` | `PickupTimeMessage[]` |
| `getCountries(GetCountriesMessage)` | `array<string, string>` |
| `getSupportedCountries()` | `Country[]` |
| `findCourierPoints(GetPointMessage)` | `CourierPoint[]` |
| `getCourierPoint(string $id, array $couriers)` | `CourierPoint\|null` |

Most integrations start with `availableCarriers()`. Put a `Carrier`'s `serviceCode` into
`CreateOrderMessage::$courier`. With `additionalData: true`, the response also lists what each
carrier supports: shipment types, pack limits, whether it needs a pickup date, which additional
services it offers, and which point map it uses. The SDK doesn't validate against this data because
it varies by carrier and account. Read it from here instead.

```php
$carriers = $client->courier()->availableCarriers(
    new AvailableCarriersMessage(additionalData: true),
);
```

### Finding a pickup point

There are three ways to find a pickup point. Each one suits a different situation.

**1. Search across carriers.** Search by query, location or filter:

```php
use Polkurier\Webservice\Request\Courier\GetPointMessage;

$points = $client->courier()->findCourierPoints(new GetPointMessage(
    couriers: ['INPOST_PACZKOMAT'],
    searchQuery: 'Poznań',
    limit: 5,
));
```

**2. Look one up by id.** Use this when the customer has already chosen a point and you want to
check it still exists:

```php
$point = $client->courier()->getCourierPoint('POZ01A', ['INPOST_PACZKOMAT']);

if ($point === null) {
    // The id is unknown to that carrier. This is an absence, not an error.
}
```

The carrier list is required even if the id looks unique.

**3. Show the customer a map.** `$client->geo()->getMapToken()` returns a short-lived token for the
pickup point map widget, which reports the chosen point back to your page. Use the map when the
customer picks the point, and the methods above when your code does.

### Collections

```php
$pickup = $client->courier()->pickupCourier($pickupMessage);       // one shipment
$times  = $client->courier()->getCourierPickupTime($whenMessage);  // available days and windows
```

To book one collection for *several* shipments, use
[`orders()->createMultiOrderPickup()`](#orders). Check `orders()->isMultiPickupAvailable()` first.

### Country coverage

`getSupportedCountries()` returns the countries Polkurier serves, as `Country[]` with their
regions. `getCountries(GetCountriesMessage)` returns the countries a particular carrier delivers
to, as a flat map of codes to names. Use the first to build a form and the second to check a
destination for one carrier.

---

orders()
--------

`Api\Service\OrdersServiceInterface`: pricing, placing and managing orders.

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

`orderValuation()` and `createOrder()` take the same `CreateOrderMessage`.

> A successful valuation only gives you a price. A payload that prices without errors can still be
> rejected when you place the order, so don't use valuation to check whether an order will go
> through.

`orderValuation()` returns a decoded array instead of a mapped object. Its response has no stable
structure, so the SDK returns it as received. It is one of two such endpoints. See
[Messages: unmapped responses](messages.md#the-two-unmapped-responses).

Full example: [Recipes: placing an order](recipes.md#placing-an-order).

### Reading orders back

```php
$list   = $client->orders()->getOrders($filter);        // OrderListMessage: paged, full Order objects
$status = $client->orders()->getStatus($statusMessage); // OrderStatusMessage: one order's delivery state
```

`getOrders()` is the general query. It filters by date, status, carrier, cash-on-delivery range,
free text and your own external identifiers, and can optionally include `packs`, `items` and
`files`. Results are paged (see [Recipes: paging](recipes.md#paging-through-a-list)). The maximum
page size is `GetOrdersMessage::MAX_PAGE_SIZE` (100), and the SDK rejects anything larger before
sending it.

`getStatus()` returns the current delivery state of one order, optionally with its scan history.
It is the lighter call. Use it for tracking, and `getOrders()` for reconciliation.

Both include a status code. `Order::getStatusCode()` returns a plain string, and
`Enum\Order\OrderStatusCodeEnum` holds the known values. Compare against the enum instead of string
literals:

```php
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;

if ($order->getStatusCode() === OrderStatusCodeEnum::Delivered->value) { … }
```

### Documents

```php
$labels   = $client->orders()->getLabel($doc);      // shipping labels, one PDF for all of them
$protocol = $client->orders()->getProtocol($doc);   // the handover list the courier signs
$file     = $client->orders()->getOrderFile($msg);  // a document attached to one order
```

`getLabel()` and `getProtocol()` both take `GetDocumentMessage`, which accepts a **list** of order
numbers. You get labels for several orders back as one PDF from a single call.

Each result has the base64 content in `getFile()` and the raw bytes in `getDecodedFile()`:

```php
file_put_contents('labels.pdf', $labels->getDecodedFile());     // write it out
echo '<embed src="data:application/pdf;base64,' . $labels->getFile() . '">';  // hand it to a browser
```

`getLabel()` also accepts a `LabelFileFormatEnum` for formats other than PDF, such as ZPL, if the
carrier supports them.

### Collections for several orders

```php
if ($client->orders()->isMultiPickupAvailable()->isAvailable()) {
    $result = $client->orders()->createMultiOrderPickup($request);
}
```

Not every account can book these, so check availability first. For a single shipment, use
[`courier()->pickupCourier()`](#courier).

addresses()
-----------

`Api\Service\AddressesServiceInterface`: the account's address book.

| Method | Returns |
|--------|---------|
| `getAddresses(?Address\GetMessage)` | `Address[]` |
| `saveAddress(Address\SaveMessage)` | `Address` |
| `deleteAddress(Address\DeleteMessage)` | `bool` |

`saveAddress()` creates and updates. With an id in the message it updates that entry, and without
one it creates a new entry. The API uses one endpoint for both. Bank accounts and pack templates
work the same way.

You send a `Request\Address\SaveMessage` and get back a `Response\Address`. These are different
classes, as with requests and responses throughout the SDK. See
[Messages: why requests and responses are split](messages.md#why-requests-and-responses-are-split).

---

bankAccounts()
--------------

`Api\Service\BankAccountsServiceInterface`: accounts that cash on delivery is paid out to.

| Method | Returns |
|--------|---------|
| `getBankAccounts()` | `BankAccount[]` |
| `saveBankAccount(BankAccount\SaveMessage)` | `BankAccount` |
| `deleteBankAccount(DeleteBankAccountMessage)` | `bool` |

The account number is checked before it is sent. The `Validator\Rule\Iban` rule verifies the
ISO 13616 check digits, and accepts either a full IBAN or a national number without the country
prefix. The check is strict because this field decides where collected money is paid, and two
swapped digits could send it to someone else's account.

Depending on the account's settings, changing a bank account may require a
[two-factor token](#twofactortoken).

---

packTemplates()
---------------

`Api\Service\PackTemplatesServiceInterface`: saved parcel templates.

| Method | Returns |
|--------|---------|
| `getPackTemplates()` | `PackTemplate[]` |
| `savePackTemplates(PackTemplate\SaveMessage)` | `PackTemplate` |
| `deletePackTemplates(Common\DeleteMessage)` | `bool` |

Templates are optional. An order always carries its parcel dimensions in `PackMessage`. Use
templates if you ship in standard box sizes and don't want to repeat them for every order.

---

geo()
-----

`Api\Service\GeoServiceInterface`: city lookup and map tokens.

| Method | Returns |
|--------|---------|
| `findCity(FindCityMessage)` | `CityMessage[]` |
| `getMapToken()` | `TokenMessage` |

`findCity()` returns the localities for a postcode. Use it for address autocompletion: when the
customer types `61-001`, you suggest the cities the API returns so they don't type one the carrier
won't recognise.

`getMapToken()` returns a short-lived token for the pickup point map widget. Pass the token to the
browser, and put the point the widget returns into the order's address.

---

account()
---------

`Api\Service\AccountServiceInterface`: the account's profile, settings, credentials and phone
numbers. It has the most methods, though most integrations use few of them.

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

Call `getAccountInfo()` once at start-up, even if you don't change anything. It returns the
balances, outstanding payments and PIN lock settings, which decide whether other calls need
confirming. The SDK's local validation doesn't check any of this account data, and the same goes
for `system()->getSystemInfo()` and `courier()->availableCarriers()`.

`savePayerAddress()` saves the billing, correspondence or contact address. Choose which one with
`Enum\Account\PayerAddressTypeEnum`.

> `acceptRegulations()` records that the customer accepts the current terms, which is a legal act on
> their behalf. Only call it when the customer has actually accepted them, never automatically at
> start-up.

---

accountVerification()
---------------------

`Api\Service\AccountVerificationServiceInterface`: identity verification, for accounts that need
it before shipping.

| Method | Returns |
|--------|---------|
| `register(?RegisterAccountVerificationMessage)` | `AccountVerificationMessage` |
| `getInfo()` | `?AccountVerificationMessage` |

`register()` starts a verification and returns the token needed to complete it. The customer
finishes the process outside your application. `getInfo()` returns the latest attempt and its
result, or `null` if none has been started. Poll it to find out whether verification succeeded.

---

invoices()
----------

`Api\Service\InvoicesServiceInterface`.

| Method | Returns |
|--------|---------|
| `getInvoices(GetInvoicesMessage)` | `InvoiceListMessage` |
| `getInvoicePdf(GetInvoiceDocumentMessage)` | `InvoiceDocumentMessage` |
| `getInvoiceSummaryList(GetInvoiceDocumentMessage)` | `InvoiceDocumentMessage` |

`getInvoicePdf()` returns the invoice document. `getInvoiceSummaryList()` returns its itemised
summary, which is more useful for reconciling line by line. Both take the same message and return
base64, like order documents.

---

payments()
----------

`Api\Service\PaymentsServiceInterface`: online payments and the prepaid balance.

| Method | Returns |
|--------|---------|
| `registerPayment(RegisterPaymentMessage)` | `RegisteredPaymentMessage` |
| `getPaymentInfo(GetPaymentInfoMessage)` | `PaymentInfoMessage` |
| `getPrepaidHistory(?GetPrepaidHistoryMessage)` | `PrepaidHistoryMessage` |

`registerPayment()` takes a `PaymentModeEnum` that says what the payment is for. Send an amount
only for top-ups (`PaymentModeEnum::Prepaid`). A top-up needs an amount greater than zero, and the
SDK checks this before sending.

`getPaymentInfo()` returns the current state of a registered payment. You have to poll it. There
is no webhook.

---

moneyBox()
----------

`Api\Service\MoneyBoxServiceInterface`: cash-on-delivery funds held on the account, and paying
them out.

| Method | Returns |
|--------|---------|
| `getConfig()` | `MoneyBoxConfigMessage` |
| `calculatePayoutFee(?CalculateMoneyBoxPayoutFeeMessage)` | `MoneyBoxFeeMessage` |
| `createPayout(CreateMoneyBoxPayoutMessage)` | `MoneyBoxPayoutMessage` |
| `cancelPayout(CancelMoneyBoxPayoutMessage)` | `MoneyBoxPayoutMessage` |
| `getPayouts(?GetMoneyBoxPayoutsMessage)` | `MoneyBoxPayoutListMessage` |

`calculatePayoutFee()` returns what a payout would cost, without requesting it. Call it first. The
fee depends on the payout type and on who pays the fee (`MoneyBoxFeePaymentTypeEnum`).

> `createPayout()` moves real money. Depending on the account's settings, it may need a security
> PIN, a [two-factor token](#twofactortoken), or both. `cancelPayout()` only works until the payout
> has been sent.

---

tickets()
---------

`Api\Service\TicketsServiceInterface`: support tickets and contact forms.

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

There are two ways to contact support. A **ticket** is a conversation you can read and reply to.
Open it with `createTicket()`, read it with `getTicketMessages()`, and reply with
`createTicketMessage()`. A **contact form** is a one-way submission with fields defined by the
API. `getContactForm()` returns the form definition and `saveContactForm()` submits it. Use a
ticket if you want to get the answer back in your own system.

`getContactForm()` is the other endpoint whose response shape depends on the request, so it also
returns a decoded array.

`GetTicketsMessage::MAX_PAGE_SIZE` is 250 (for orders it is 100).

---

knowledgeBase()
---------------

`Api\Service\KnowledgeBaseServiceInterface`: help articles.

| Method | Returns |
|--------|---------|
| `getIndex()` | `KnowledgeBaseEntryMessage[]` |
| `search(SearchKnowledgeBaseMessage)` | `KnowledgeBaseEntryMessage[]` |
| `getArticle(GetKnowledgeBaseArticleMessage)` | `KnowledgeBaseArticleMessage` |

You can fetch the flat index of all categories and articles and filter it yourself, or use the
API's search. The index changes rarely, so you can cache it. Search works better for
search-as-you-type.

---

twoFactorToken()
----------------

`Api\Service\TwoFactorTokenServiceInterface`: confirmation codes for operations that need them.

| Method | Returns |
|--------|---------|
| `twoFactorTokenRegister(RegisterMessage)` | `TwoFactorToken` |
| `twoFactorTokenResend(Common\TokenMessage)` | `TwoFactorToken` |
| `twoFactorTokenVerify(VerifyMessage)` | `ValidityMessage` |
| `twoFactorTokenChangeTransportMethod(ChangeTransportMethodMessage)` | `TwoFactorToken` |

Register a token with the `TwoFactorNamespaceEnum` value for the operation. The customer receives
a code. You then either verify it here, or pass the token and code to the operation itself. If the
code doesn't arrive, `twoFactorTokenResend()` sends it again on the same channel, and
`twoFactorTokenChangeTransportMethod()` switches to another channel.

Full example: [Recipes: two-factor confirmation](recipes.md#two-factor-confirmation).

---

system()
--------

`Api\Service\SystemServiceInterface`.

| Method | Returns |
|--------|---------|
| `getSystemInfo()` | `SystemInfoMessage` |
| `getCouriersMessages()` | `CourierMessage[]` |
| `validateCouponCode(ValidateCouponMessage)` | `CouponValidationMessage` |

`getSystemInfo()` returns the limits a client should check against: coupon, security and ticket
limits. The SDK doesn't hard-code them, just as it doesn't hard-code carrier rules, because they
can change without a new SDK release. Read them from here.

`getCouriersMessages()` returns carrier notices (delays, suspensions, general information), each
with a `CourierMessageSeverityEnum`. Show them to the people placing orders. They often explain
rejections.

`validateCouponCode()` checks a coupon on its own or against a specific order. Checking against an
order needs the carrier, the shipment type and the parcels. If any of them are missing, the SDK
reports all the missing ones together.

---

utils()
-------

`Api\Service\UtilsServiceInterface`: reachability and credential checks.

| Method | Returns |
|--------|---------|
| `heartbeat()` | `HeartbeatMessage` |
| `testAuthApi(AuthorizationMessage)` | `AuthConfirmationMessage` |

`heartbeat()` sends no credentials, so use it for health checks. It tells
you whether the API is reachable and returns the server time, whether or not your token is valid.

`testAuthApi()` takes its own `AuthorizationMessage` instead of using the configured one. You can
use it to check credentials before saving them, e.g. in a settings form.

```php
try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $typed, login: $id));
    // good
} catch (AuthorizationException) {
    // not good, and nothing was saved
}
```
