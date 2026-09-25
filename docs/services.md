Services
========

The client has one service per functional area and one method per API endpoint. Each accessor
returns an interface from [`Polkurier\Webservice\Api\Service\`](../src/Api/Service/). Type-hint the interface, so your code
also accepts a replacement implementation.

| Accessor | Interface | Covers |
|----------|-----------|--------|
| [`courier()`](#courier) | [`CourierServiceInterface`](../src/Api/Service/CourierServiceInterface.php) | Carriers, collections, pickup points, country coverage |
| [`orders()`](#orders) | [`OrdersServiceInterface`](../src/Api/Service/OrdersServiceInterface.php) | Pricing, placing, tracking, cancelling, documents |
| [`addresses()`](#addresses) | [`AddressesServiceInterface`](../src/Api/Service/AddressesServiceInterface.php) | The account's address book |
| [`bankAccounts()`](#bankaccounts) | [`BankAccountsServiceInterface`](../src/Api/Service/BankAccountsServiceInterface.php) | Accounts cash on delivery is settled to |
| [`packTemplates()`](#packtemplates) | [`PackTemplatesServiceInterface`](../src/Api/Service/PackTemplatesServiceInterface.php) | Saved parcel templates |
| [`geo()`](#geo) | [`GeoServiceInterface`](../src/Api/Service/GeoServiceInterface.php) | City lookup and map tokens |
| [`account()`](#account) | [`AccountServiceInterface`](../src/Api/Service/AccountServiceInterface.php) | Profile, balances, preferences, credentials, phone numbers |
| [`accountVerification()`](#accountverification) | [`AccountVerificationServiceInterface`](../src/Api/Service/AccountVerificationServiceInterface.php) | Identity verification |
| [`invoices()`](#invoices) | [`InvoicesServiceInterface`](../src/Api/Service/InvoicesServiceInterface.php) | Invoices and their documents |
| [`payments()`](#payments) | [`PaymentsServiceInterface`](../src/Api/Service/PaymentsServiceInterface.php) | Online payments and the prepaid balance |
| [`moneyBox()`](#moneybox) | [`MoneyBoxServiceInterface`](../src/Api/Service/MoneyBoxServiceInterface.php) | Collected funds and withdrawing them |
| [`tickets()`](#tickets) | [`TicketsServiceInterface`](../src/Api/Service/TicketsServiceInterface.php) | Support tickets and contact forms |
| [`knowledgeBase()`](#knowledgebase) | [`KnowledgeBaseServiceInterface`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | Help articles |
| [`twoFactorToken()`](#twofactortoken) | [`TwoFactorTokenServiceInterface`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | Confirmation codes for guarded operations |
| [`system()`](#system) | [`SystemServiceInterface`](../src/Api/Service/SystemServiceInterface.php) | Service limits, carrier notices, coupon checks |
| [`utils()`](#utils) | [`UtilsServiceInterface`](../src/Api/Service/UtilsServiceInterface.php) | Reachability and credential checks |

Reading a method signature
--------------------------

Method signatures follow three patterns:

```php
availableCarriers(?AvailableCarriersMessage $message = null): array   // all filters optional
createOrder(CreateOrderMessage $message): CreatedOrderMessage         // payload required
getBankAccounts(): array                                             // no parameters at all
```

A method that returns a [`CreatedOrderMessage`](../src/Response/Order/CreatedOrderMessage.php) either returns one or throws. It never returns null
because the API sent something unexpected. The one nullable method is [`CourierServiceInterface::getCourierPoint()`](../src/Api/Service/CourierServiceInterface.php): an id
that matches no point returns null. It is marked in the table below.

Every method can throw [`AuthorizationException`](../src/Exception/AuthorizationException.php), [`ConnectionException`](../src/Exception/ConnectionException.php),
[`MalformedResponseException`](../src/Exception/MalformedResponseException.php), [`ValidationException`](../src/Exception/ValidationException.php) and [`WebserviceException`](../src/Exception/WebserviceException.php). This page doesn't
repeat that for each method. See [Errors](errors.md).

Each interface method's docblock lists the endpoint it calls, a sample payload, the possible
success responses and the most common errors. This page is an overview. The interfaces have the
details.

---

courier()
---------

[`Api\Service\CourierServiceInterface`](../src/Api/Service/CourierServiceInterface.php): carriers, collections, pickup points, country coverage.

| Method | Returns |
|--------|---------|
| [`CourierServiceInterface::availableCarriers(?AvailableCarriersMessage)`](../src/Api/Service/CourierServiceInterface.php) | `Carrier[]` |
| [`CourierServiceInterface::pickupCourier(PickupMessage)`](../src/Api/Service/CourierServiceInterface.php) | [`PickupResponseMessage`](../src/Response/Courier/PickupResponseMessage.php) |
| [`CourierServiceInterface::getCourierPickupTime(GetPickupTimeMessage)`](../src/Api/Service/CourierServiceInterface.php) | `PickupTimeMessage[]` |
| [`CourierServiceInterface::getCountries(GetCountriesMessage)`](../src/Api/Service/CourierServiceInterface.php) | `array<string, string>` |
| [`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) | `Country[]` |
| [`CourierServiceInterface::findCourierPoints(GetPointMessage)`](../src/Api/Service/CourierServiceInterface.php) | `CourierPoint[]` |
| [`CourierServiceInterface::getCourierPoint(string $id, array $couriers)`](../src/Api/Service/CourierServiceInterface.php) | `CourierPoint\|null` |

Most integrations start with [`CourierServiceInterface::availableCarriers()`](../src/Api/Service/CourierServiceInterface.php). Put a [`Carrier`](../src/Response/Carrier.php)'s `serviceCode` into
[`CreateOrderMessage::$courier`](../src/Request/Order/CreateOrderMessage.php). With `additionalData: true`, the response also lists what each
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

**3. Show the customer a map.** [`$client->geo()->getMapToken()`](../src/Api/Service/GeoServiceInterface.php) returns a short-lived token for the
pickup point map widget, which reports the chosen point back to your page. Use the map when the
customer picks the point, and the methods above when your code does.

### Collections

```php
$pickup = $client->courier()->pickupCourier($pickupMessage);       // one shipment
$times  = $client->courier()->getCourierPickupTime($whenMessage);  // available days and windows
```

To book one collection for *several* shipments, use
[`orders()->createMultiOrderPickup()`](#orders). Check [`$client->orders()->isMultiPickupAvailable()`](../src/Api/Service/OrdersServiceInterface.php) first.

### Country coverage

[`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) returns the countries Polkurier serves, as `Country[]` with their
regions. [`CourierServiceInterface::getCountries(GetCountriesMessage)`](../src/Api/Service/CourierServiceInterface.php) returns the countries a particular carrier delivers
to, as a flat map of codes to names. Use the first to build a form and the second to check a
destination for one carrier.

---

orders()
--------

[`Api\Service\OrdersServiceInterface`](../src/Api/Service/OrdersServiceInterface.php): pricing, placing and managing orders.

| Method | Returns |
|--------|---------|
| [`OrdersServiceInterface::orderValuation(CreateOrderMessage)`](../src/Api/Service/OrdersServiceInterface.php) | `array<array-key, mixed>` |
| [`OrdersServiceInterface::createOrder(CreateOrderMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`CreatedOrderMessage`](../src/Response/Order/CreatedOrderMessage.php) |
| [`OrdersServiceInterface::getOrders(?GetOrdersMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderListMessage`](../src/Response/Order/OrderListMessage.php) |
| [`OrdersServiceInterface::cancelOrder(OrderIdMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`CancellationMessage`](../src/Response/Order/CancellationMessage.php) |
| [`OrdersServiceInterface::getLabel(GetDocumentMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`DocumentMessage`](../src/Response/Order/DocumentMessage.php) |
| [`OrdersServiceInterface::getProtocol(GetDocumentMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`DocumentMessage`](../src/Response/Order/DocumentMessage.php) |
| [`OrdersServiceInterface::getStatus(GetStatusMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderStatusMessage`](../src/Response/Order/OrderStatusMessage.php) |
| [`OrdersServiceInterface::isMultiPickupAvailable()`](../src/Api/Service/OrdersServiceInterface.php) | [`MultiPickupAvailabilityMessage`](../src/Response/Order/MultiPickupAvailabilityMessage.php) |
| [`OrdersServiceInterface::createMultiOrderPickup(MultiPickupRequestMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`MultiPickupResultMessage`](../src/Response/Order/MultiPickupResultMessage.php) |
| [`OrdersServiceInterface::getOrderFile(GetOrderFileMessage)`](../src/Api/Service/OrdersServiceInterface.php) | [`OrderFileMessage`](../src/Response/Order/OrderFileMessage.php) |

### Pricing and placing

[`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) and [`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php) take the same [`CreateOrderMessage`](../src/Request/Order/CreateOrderMessage.php).

> A successful valuation only gives you a price. A payload that prices without errors can still be
> rejected when you place the order, so don't use valuation to check whether an order will go
> through.

[`OrdersServiceInterface::orderValuation()`](../src/Api/Service/OrdersServiceInterface.php) returns a decoded array instead of a mapped object. Its response has no stable
structure, so the SDK returns it as received. It is one of two such endpoints. See
[Messages: unmapped responses](messages.md#the-two-unmapped-responses).

Full example: [Recipes: placing an order](recipes.md#placing-an-order).

### Reading orders back

```php
$list   = $client->orders()->getOrders($filter);        // OrderListMessage: paged, full Order objects
$status = $client->orders()->getStatus($statusMessage); // OrderStatusMessage: one order's delivery state
```

[`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) is the general query. It filters by date, status, carrier, cash-on-delivery range,
free text and your own external identifiers, and can optionally include `packs`, `items` and
`files`. Results are paged (see [Recipes: paging](recipes.md#paging-through-a-list)). The maximum
page size is [`GetOrdersMessage::MAX_PAGE_SIZE`](../src/Request/Order/GetOrdersMessage.php) (100), and the SDK rejects anything larger before
sending it.

[`OrdersServiceInterface::getStatus()`](../src/Api/Service/OrdersServiceInterface.php) returns the current delivery state of one order, optionally with its scan history.
It is the lighter call. Use it for tracking, and [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) for reconciliation.

Both include a status code. [`Order::getStatusCode()`](../src/Response/Order.php) returns a plain string, and
[`Enum\Order\OrderStatusCodeEnum`](../src/Enum/Order/OrderStatusCodeEnum.php) holds the known values. Compare against the enum instead of string
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

[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) and [`OrdersServiceInterface::getProtocol()`](../src/Api/Service/OrdersServiceInterface.php) both take [`GetDocumentMessage`](../src/Request/Order/GetDocumentMessage.php), which accepts a **list** of order
numbers. You get labels for several orders back as one PDF from a single call.

Each result is a [`DocumentMessage`](../src/Response/Order/DocumentMessage.php), with the base64 content in
[`DocumentMessage::getFile()`](../src/Response/Order/DocumentMessage.php) and the raw bytes in [`DocumentMessage::getDecodedFile()`](../src/Response/Order/DocumentMessage.php):

```php
file_put_contents('labels.pdf', $labels->getDecodedFile());     // write it out
echo '<embed src="data:application/pdf;base64,' . $labels->getFile() . '">';  // hand it to a browser
```

[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) also accepts a [`LabelFileFormatEnum`](../src/Enum/Order/LabelFileFormatEnum.php) for formats other than PDF, such as ZPL, if the
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

[`Api\Service\AddressesServiceInterface`](../src/Api/Service/AddressesServiceInterface.php): the account's address book.

| Method | Returns |
|--------|---------|
| [`AddressesServiceInterface::getAddresses(?Address\GetMessage)`](../src/Api/Service/AddressesServiceInterface.php) | `Address[]` |
| [`AddressesServiceInterface::saveAddress(Address\SaveMessage)`](../src/Api/Service/AddressesServiceInterface.php) | [`Address`](../src/Response/Address.php) |
| [`AddressesServiceInterface::deleteAddress(Address\DeleteMessage)`](../src/Api/Service/AddressesServiceInterface.php) | `bool` |

[`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) creates and updates. With an id in the message it updates that entry, and without
one it creates a new entry. The API uses one endpoint for both. Bank accounts and pack templates
work the same way.

You send a [`Request\Address\SaveMessage`](../src/Request/Address/SaveMessage.php) and get back a [`Response\Address`](../src/Response/Address.php). These are different
classes, as with requests and responses throughout the SDK. See
[Messages: why requests and responses are split](messages.md#why-requests-and-responses-are-split).

---

bankAccounts()
--------------

[`Api\Service\BankAccountsServiceInterface`](../src/Api/Service/BankAccountsServiceInterface.php): accounts that cash on delivery is paid out to.

| Method | Returns |
|--------|---------|
| [`BankAccountsServiceInterface::getBankAccounts()`](../src/Api/Service/BankAccountsServiceInterface.php) | `BankAccount[]` |
| [`BankAccountsServiceInterface::saveBankAccount(BankAccount\SaveMessage)`](../src/Api/Service/BankAccountsServiceInterface.php) | [`BankAccount`](../src/Response/BankAccount.php) |
| [`BankAccountsServiceInterface::deleteBankAccount(DeleteBankAccountMessage)`](../src/Api/Service/BankAccountsServiceInterface.php) | `bool` |

The account number is checked before it is sent. The [`Validator\Rule\Iban`](../src/Validator/Rule/Iban.php) rule verifies the
ISO 13616 check digits, and accepts either a full IBAN or a national number without the country
prefix. The check is strict because this field decides where collected money is paid, and two
swapped digits could send it to someone else's account.

Depending on the account's settings, changing a bank account may require a
[two-factor token](#twofactortoken).

---

packTemplates()
---------------

[`Api\Service\PackTemplatesServiceInterface`](../src/Api/Service/PackTemplatesServiceInterface.php): saved parcel templates.

| Method | Returns |
|--------|---------|
| [`PackTemplatesServiceInterface::getPackTemplates()`](../src/Api/Service/PackTemplatesServiceInterface.php) | `PackTemplate[]` |
| [`PackTemplatesServiceInterface::savePackTemplates(PackTemplate\SaveMessage)`](../src/Api/Service/PackTemplatesServiceInterface.php) | [`PackTemplate`](../src/Response/PackTemplate.php) |
| [`PackTemplatesServiceInterface::deletePackTemplates(Common\DeleteMessage)`](../src/Api/Service/PackTemplatesServiceInterface.php) | `bool` |

Templates are optional. An order always carries its parcel dimensions in [`PackMessage`](../src/Request/Order/PackMessage.php). Use
templates if you ship in standard box sizes and don't want to repeat them for every order.

---

geo()
-----

[`Api\Service\GeoServiceInterface`](../src/Api/Service/GeoServiceInterface.php): city lookup and map tokens.

| Method | Returns |
|--------|---------|
| [`GeoServiceInterface::findCity(FindCityMessage)`](../src/Api/Service/GeoServiceInterface.php) | `CityMessage[]` |
| [`GeoServiceInterface::getMapToken()`](../src/Api/Service/GeoServiceInterface.php) | [`Geo\TokenMessage`](../src/Response/Geo/TokenMessage.php) |

[`GeoServiceInterface::findCity()`](../src/Api/Service/GeoServiceInterface.php) returns the localities for a postcode. Use it for address autocompletion: when the
customer types `61-001`, you suggest the cities the API returns so they don't type one the carrier
won't recognise.

[`GeoServiceInterface::getMapToken()`](../src/Api/Service/GeoServiceInterface.php) returns a short-lived token for the pickup point map widget. Pass the token to the
browser, and put the point the widget returns into the order's address.

---

account()
---------

[`Api\Service\AccountServiceInterface`](../src/Api/Service/AccountServiceInterface.php): the account's profile, settings, credentials and phone
numbers. It has the most methods, though most integrations use few of them.

| Method | Returns |
|--------|---------|
| [`AccountServiceInterface::getAccountInfo(?GetAccountInfoMessage)`](../src/Api/Service/AccountServiceInterface.php) | [`AccountInfoMessage`](../src/Response/Account/AccountInfoMessage.php) |
| [`AccountServiceInterface::saveAccountSettings(SaveAccountSettingsMessage)`](../src/Api/Service/AccountServiceInterface.php) | [`AccountInfoMessage`](../src/Response/Account/AccountInfoMessage.php) |
| [`AccountServiceInterface::changeDefaultCourier(ChangeDefaultCourierMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::changeDefaultSenderPoint(ChangeDefaultSenderPointMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::savePayerAddress(SavePayerAddressMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::changePassword(ChangePasswordMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::changePhonePin(ChangePinMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::validateUserPin(ValidatePinMessage)`](../src/Api/Service/AccountServiceInterface.php) | [`PinValidityMessage`](../src/Response/Account/PinValidityMessage.php) |
| [`AccountServiceInterface::getRegulations(?GetRegulationsMessage)`](../src/Api/Service/AccountServiceInterface.php) | [`RegulationsMessage`](../src/Response/Account/RegulationsMessage.php) |
| [`AccountServiceInterface::acceptRegulations(AcceptRegulationsMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |
| [`AccountServiceInterface::getPhoneNumbers()`](../src/Api/Service/AccountServiceInterface.php) | `PhoneNumberMessage[]` |
| [`AccountServiceInterface::savePhoneNumber(SavePhoneNumberMessage)`](../src/Api/Service/AccountServiceInterface.php) | [`PhoneNumberMessage`](../src/Response/Account/PhoneNumberMessage.php) |
| [`AccountServiceInterface::deletePhoneNumber(DeletePhoneNumberMessage)`](../src/Api/Service/AccountServiceInterface.php) | `bool` |

Call [`AccountServiceInterface::getAccountInfo()`](../src/Api/Service/AccountServiceInterface.php) once at start-up, even if you don't change anything. It returns the
balances, outstanding payments and PIN lock settings, which decide whether other calls need
confirming. The SDK's local validation doesn't check any of this account data, and the same goes
for [`$client->system()->getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) and [`$client->courier()->availableCarriers()`](../src/Api/Service/CourierServiceInterface.php).

[`AccountServiceInterface::savePayerAddress()`](../src/Api/Service/AccountServiceInterface.php) saves the billing, correspondence or contact address. Choose which one with
[`Enum\Account\PayerAddressTypeEnum`](../src/Enum/Account/PayerAddressTypeEnum.php).

> [`AccountServiceInterface::acceptRegulations()`](../src/Api/Service/AccountServiceInterface.php) records that the customer accepts the current terms, which is a legal act on
> their behalf. Only call it when the customer has actually accepted them, never automatically at
> start-up.

---

accountVerification()
---------------------

[`Api\Service\AccountVerificationServiceInterface`](../src/Api/Service/AccountVerificationServiceInterface.php): identity verification, for accounts that need
it before shipping.

| Method | Returns |
|--------|---------|
| [`AccountVerificationServiceInterface::register(?RegisterAccountVerificationMessage)`](../src/Api/Service/AccountVerificationServiceInterface.php) | [`AccountVerificationMessage`](../src/Response/Account/AccountVerificationMessage.php) |
| [`AccountVerificationServiceInterface::getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php) | `?AccountVerificationMessage` |

[`AccountVerificationServiceInterface::register()`](../src/Api/Service/AccountVerificationServiceInterface.php) starts a verification and returns the token needed to complete it. The customer
finishes the process outside your application. [`AccountVerificationServiceInterface::getInfo()`](../src/Api/Service/AccountVerificationServiceInterface.php) returns the latest attempt and its
result, or `null` if none has been started. Poll it to find out whether verification succeeded.

---

invoices()
----------

[`Api\Service\InvoicesServiceInterface`](../src/Api/Service/InvoicesServiceInterface.php).

| Method | Returns |
|--------|---------|
| [`InvoicesServiceInterface::getInvoices(GetInvoicesMessage)`](../src/Api/Service/InvoicesServiceInterface.php) | [`InvoiceListMessage`](../src/Response/Invoice/InvoiceListMessage.php) |
| [`InvoicesServiceInterface::getInvoicePdf(GetInvoiceDocumentMessage)`](../src/Api/Service/InvoicesServiceInterface.php) | [`InvoiceDocumentMessage`](../src/Response/Invoice/InvoiceDocumentMessage.php) |
| [`InvoicesServiceInterface::getInvoiceSummaryList(GetInvoiceDocumentMessage)`](../src/Api/Service/InvoicesServiceInterface.php) | [`InvoiceDocumentMessage`](../src/Response/Invoice/InvoiceDocumentMessage.php) |

[`InvoicesServiceInterface::getInvoicePdf()`](../src/Api/Service/InvoicesServiceInterface.php) returns the invoice document. [`InvoicesServiceInterface::getInvoiceSummaryList()`](../src/Api/Service/InvoicesServiceInterface.php) returns its itemised
summary, which is more useful for reconciling line by line. Both take the same message and return
base64, like order documents.

---

payments()
----------

[`Api\Service\PaymentsServiceInterface`](../src/Api/Service/PaymentsServiceInterface.php): online payments and the prepaid balance.

| Method | Returns |
|--------|---------|
| [`PaymentsServiceInterface::registerPayment(RegisterPaymentMessage)`](../src/Api/Service/PaymentsServiceInterface.php) | [`RegisteredPaymentMessage`](../src/Response/Payment/RegisteredPaymentMessage.php) |
| [`PaymentsServiceInterface::getPaymentInfo(GetPaymentInfoMessage)`](../src/Api/Service/PaymentsServiceInterface.php) | [`PaymentInfoMessage`](../src/Response/Payment/PaymentInfoMessage.php) |
| [`PaymentsServiceInterface::getPrepaidHistory(?GetPrepaidHistoryMessage)`](../src/Api/Service/PaymentsServiceInterface.php) | [`PrepaidHistoryMessage`](../src/Response/Payment/PrepaidHistoryMessage.php) |

[`PaymentsServiceInterface::registerPayment()`](../src/Api/Service/PaymentsServiceInterface.php) takes a [`PaymentModeEnum`](../src/Enum/Payment/PaymentModeEnum.php) that says what the payment is for. Send an amount
only for top-ups ([`PaymentModeEnum::Prepaid`](../src/Enum/Payment/PaymentModeEnum.php)). A top-up needs an amount greater than zero, and the
SDK checks this before sending.

[`PaymentsServiceInterface::getPaymentInfo()`](../src/Api/Service/PaymentsServiceInterface.php) returns the current state of a registered payment. You have to poll it. There
is no webhook.

---

moneyBox()
----------

[`Api\Service\MoneyBoxServiceInterface`](../src/Api/Service/MoneyBoxServiceInterface.php): cash-on-delivery funds held on the account, and paying
them out.

| Method | Returns |
|--------|---------|
| [`MoneyBoxServiceInterface::getConfig()`](../src/Api/Service/MoneyBoxServiceInterface.php) | [`MoneyBoxConfigMessage`](../src/Response/MoneyBox/MoneyBoxConfigMessage.php) |
| [`MoneyBoxServiceInterface::calculatePayoutFee(?CalculateMoneyBoxPayoutFeeMessage)`](../src/Api/Service/MoneyBoxServiceInterface.php) | [`MoneyBoxFeeMessage`](../src/Response/MoneyBox/MoneyBoxFeeMessage.php) |
| [`MoneyBoxServiceInterface::createPayout(CreateMoneyBoxPayoutMessage)`](../src/Api/Service/MoneyBoxServiceInterface.php) | [`MoneyBoxPayoutMessage`](../src/Response/MoneyBox/MoneyBoxPayoutMessage.php) |
| [`MoneyBoxServiceInterface::cancelPayout(CancelMoneyBoxPayoutMessage)`](../src/Api/Service/MoneyBoxServiceInterface.php) | [`MoneyBoxPayoutMessage`](../src/Response/MoneyBox/MoneyBoxPayoutMessage.php) |
| [`MoneyBoxServiceInterface::getPayouts(?GetMoneyBoxPayoutsMessage)`](../src/Api/Service/MoneyBoxServiceInterface.php) | [`MoneyBoxPayoutListMessage`](../src/Response/MoneyBox/MoneyBoxPayoutListMessage.php) |

[`MoneyBoxServiceInterface::calculatePayoutFee()`](../src/Api/Service/MoneyBoxServiceInterface.php) returns what a payout would cost, without requesting it. Call it first. The
fee depends on the payout type and on who pays the fee ([`MoneyBoxFeePaymentTypeEnum`](../src/Enum/MoneyBox/MoneyBoxFeePaymentTypeEnum.php)).

> [`MoneyBoxServiceInterface::createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) moves real money. Depending on the account's settings, it may need a security
> PIN, a [two-factor token](#twofactortoken), or both. [`MoneyBoxServiceInterface::cancelPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) only works until the payout
> has been sent.

---

tickets()
---------

[`Api\Service\TicketsServiceInterface`](../src/Api/Service/TicketsServiceInterface.php): support tickets and contact forms.

| Method | Returns |
|--------|---------|
| [`TicketsServiceInterface::getContactTopics()`](../src/Api/Service/TicketsServiceInterface.php) | `ContactTopicMessage[]` |
| [`TicketsServiceInterface::createTicket(CreateTicketMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketMessage`](../src/Response/Ticket/TicketMessage.php) |
| [`TicketsServiceInterface::getTickets(?GetTicketsMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketListMessage`](../src/Response/Ticket/TicketListMessage.php) |
| [`TicketsServiceInterface::getTicketMessages(TicketIdMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketMessage`](../src/Response/Ticket/TicketMessage.php) |
| [`TicketsServiceInterface::createTicketMessage(CreateTicketMessageMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketMessage`](../src/Response/Ticket/TicketMessage.php) |
| [`TicketsServiceInterface::setTicketStatus(SetTicketStatusMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketMessage`](../src/Response/Ticket/TicketMessage.php) |
| [`TicketsServiceInterface::getTicketMessageFile(GetTicketMessageFileMessage)`](../src/Api/Service/TicketsServiceInterface.php) | [`TicketFileContentMessage`](../src/Response/Ticket/TicketFileContentMessage.php) |
| [`TicketsServiceInterface::getContactForm(GetContactFormMessage)`](../src/Api/Service/TicketsServiceInterface.php) | `array<array-key, mixed>` |
| [`TicketsServiceInterface::saveContactForm(SaveContactFormMessage)`](../src/Api/Service/TicketsServiceInterface.php) | `bool` |

There are two ways to contact support. A **ticket** is a conversation you can read and reply to.
Open it with [`TicketsServiceInterface::createTicket()`](../src/Api/Service/TicketsServiceInterface.php), read it with [`TicketsServiceInterface::getTicketMessages()`](../src/Api/Service/TicketsServiceInterface.php), and reply with
[`TicketsServiceInterface::createTicketMessage()`](../src/Api/Service/TicketsServiceInterface.php). A **contact form** is a one-way submission with fields defined by the
API. [`TicketsServiceInterface::getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) returns the form definition and [`TicketsServiceInterface::saveContactForm()`](../src/Api/Service/TicketsServiceInterface.php) submits it. Use a
ticket if you want to get the answer back in your own system.

[`TicketsServiceInterface::getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) is the other endpoint whose response shape depends on the request, so it also
returns a decoded array.

[`GetTicketsMessage::MAX_PAGE_SIZE`](../src/Request/Ticket/GetTicketsMessage.php) is 250 (for orders it is 100).

---

knowledgeBase()
---------------

[`Api\Service\KnowledgeBaseServiceInterface`](../src/Api/Service/KnowledgeBaseServiceInterface.php): help articles.

| Method | Returns |
|--------|---------|
| [`KnowledgeBaseServiceInterface::getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | `KnowledgeBaseEntryMessage[]` |
| [`KnowledgeBaseServiceInterface::search(SearchKnowledgeBaseMessage)`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | `KnowledgeBaseEntryMessage[]` |
| [`KnowledgeBaseServiceInterface::getArticle(GetKnowledgeBaseArticleMessage)`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | [`KnowledgeBaseArticleMessage`](../src/Response/KnowledgeBase/KnowledgeBaseArticleMessage.php) |

You can fetch the flat index of all categories and articles and filter it yourself, or use the
API's search. The index changes rarely, so you can cache it. Search works better for
search-as-you-type.

---

twoFactorToken()
----------------

[`Api\Service\TwoFactorTokenServiceInterface`](../src/Api/Service/TwoFactorTokenServiceInterface.php): confirmation codes for operations that need them.

| Method | Returns |
|--------|---------|
| [`TwoFactorTokenServiceInterface::twoFactorTokenRegister(RegisterMessage)`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | [`TwoFactorToken`](../src/Response/TwoFactorToken.php) |
| [`TwoFactorTokenServiceInterface::twoFactorTokenResend(Common\TokenMessage)`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | [`TwoFactorToken`](../src/Response/TwoFactorToken.php) |
| [`TwoFactorTokenServiceInterface::twoFactorTokenVerify(VerifyMessage)`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | [`ValidityMessage`](../src/Response/TwoFactorToken/ValidityMessage.php) |
| [`TwoFactorTokenServiceInterface::twoFactorTokenChangeTransportMethod(ChangeTransportMethodMessage)`](../src/Api/Service/TwoFactorTokenServiceInterface.php) | [`TwoFactorToken`](../src/Response/TwoFactorToken.php) |

Register a token with the [`TwoFactorNamespaceEnum`](../src/Enum/TwoFactorToken/TwoFactorNamespaceEnum.php) value for the operation. The customer receives
a code. You then either verify it here, or pass the token and code to the operation itself. If the
code doesn't arrive, [`TwoFactorTokenServiceInterface::twoFactorTokenResend()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) sends it again on the same channel, and
[`TwoFactorTokenServiceInterface::twoFactorTokenChangeTransportMethod()`](../src/Api/Service/TwoFactorTokenServiceInterface.php) switches to another channel.

Full example: [Recipes: two-factor confirmation](recipes.md#two-factor-confirmation).

---

system()
--------

[`Api\Service\SystemServiceInterface`](../src/Api/Service/SystemServiceInterface.php).

| Method | Returns |
|--------|---------|
| [`SystemServiceInterface::getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) | [`SystemInfoMessage`](../src/Response/System/SystemInfoMessage.php) |
| [`SystemServiceInterface::getCouriersMessages()`](../src/Api/Service/SystemServiceInterface.php) | `CourierMessage[]` |
| [`SystemServiceInterface::validateCouponCode(ValidateCouponMessage)`](../src/Api/Service/SystemServiceInterface.php) | [`CouponValidationMessage`](../src/Response/System/CouponValidationMessage.php) |

[`SystemServiceInterface::getSystemInfo()`](../src/Api/Service/SystemServiceInterface.php) returns the limits a client should check against: coupon, security and ticket
limits. The SDK doesn't hard-code them, just as it doesn't hard-code carrier rules, because they
can change without a new SDK release. Read them from here.

[`SystemServiceInterface::getCouriersMessages()`](../src/Api/Service/SystemServiceInterface.php) returns carrier notices (delays, suspensions, general information), each
with a [`CourierMessageSeverityEnum`](../src/Enum/Courier/CourierMessageSeverityEnum.php). Show them to the people placing orders. They often explain
rejections.

[`SystemServiceInterface::validateCouponCode()`](../src/Api/Service/SystemServiceInterface.php) checks a coupon on its own or against a specific order. Checking against an
order needs the carrier, the shipment type and the parcels. If any of them are missing, the SDK
reports all the missing ones together.

---

utils()
-------

[`Api\Service\UtilsServiceInterface`](../src/Api/Service/UtilsServiceInterface.php): reachability and credential checks.

| Method | Returns |
|--------|---------|
| [`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) | [`HeartbeatMessage`](../src/Response/Util/HeartbeatMessage.php) |
| [`UtilsServiceInterface::testAuthApi(AuthorizationMessage)`](../src/Api/Service/UtilsServiceInterface.php) | [`AuthConfirmationMessage`](../src/Response/Util/AuthConfirmationMessage.php) |

[`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) sends no credentials, so use it for health checks. It tells
you whether the API is reachable and returns the server time, whether or not your token is valid.

[`UtilsServiceInterface::testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) takes its own [`AuthorizationMessage`](../src/Http/Request/AuthorizationMessage.php) instead of using the configured one. You can
use it to check credentials before saving them, e.g. in a settings form.

```php
try {
    $client->utils()->testAuthApi(new AuthorizationMessage(token: $typed, login: $id));
    // good
} catch (AuthorizationException) {
    // not good, and nothing was saved
}
```
