Errors
======

HTTP status is always 200
-------------------------

**The API answers HTTP 200 for everything.** A bad token, a rejected payload and a nonexistent
order all come back as 200. The transport status says nothing about the outcome, and the SDK
doesn't look at it.

The actual outcome is in the envelope's `status` field. The SDK reads it and throws an exception
for each failure case. A method that returns an [`Order`](../src/Entity/Order.php) either returns one or throws.

The hierarchy
-------------

```
Polkurier\Webservice\Exception\Exception            (extends \Exception)
├── ConnectionException
├── MalformedResponseException
├── InvalidArgumentException
│   └── ValidationException
└── WebserviceException
    └── AuthorizationException
```

Catch [`Exception`](../src/Exception/Exception.php) to handle any SDK failure in one place, or catch a subclass to tell them apart.

| Exception | Raised when | Was anything sent? | Retry? |
|-----------|-------------|--------------------|--------|
| [`ValidationException`](../src/Exception/ValidationException.php) | A payload was rejected before sending | No | Not without changing it |
| [`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php) | The SDK rejected a value locally, e.g. a builder missing a field | No | No |
| [`ConnectionException`](../src/Exception/ConnectionException.php) | The request never completed | **Unknown** | Maybe, see below |
| [`MalformedResponseException`](../src/Exception/MalformedResponseException.php) | The reply was not an API envelope, or not the promised shape | Yes | Rarely helps |
| [`AuthorizationException`](../src/Exception/AuthorizationException.php) | Credentials missing, wrong, or inactive | Yes | No |
| [`WebserviceException`](../src/Exception/WebserviceException.php) | Any other error the API reported | Yes | Depends on the error |

PHP uses the first catch block that matches, so a subclass gets its own handling only when its block
comes first: [`AuthorizationException`](../src/Exception/AuthorizationException.php) before [`WebserviceException`](../src/Exception/WebserviceException.php), and [`ValidationException`](../src/Exception/ValidationException.php) before
[`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php).

```php
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

try {
    $created = $client->orders()->createOrder($message);
} catch (ValidationException $e) {
    // Nothing was sent. Fix the payload.
} catch (AuthorizationException $e) {
    // Bad credentials. Retrying as-is won't help.
} catch (WebserviceException $e) {
    // The API refused the request and said why.
} catch (ConnectionException $e) {
    // No answer. The order may or may not exist.
} catch (MalformedResponseException $e) {
    // The reply didn't come from the API, or wasn't the expected shape.
}
```

---

[`ValidationException`](../src/Exception/ValidationException.php)
---------------------

The payload was rejected locally. **Nothing was sent and nothing changed.**

```php
$e->getValidationErrors();              // array<string, string[]>, keyed by wire field name
$e->getFieldErrors('sender.postcode');  // string[], empty when that field passed
$e->getViolations();                    // Violation[], each with a code that does not change
$e->getMessage();                       // every failure, flattened into one sentence
```

Field names are the API's own, and the array has the same shape the API uses for its own
validation errors, so you can display both the same way. See [Validation](validation.md) for
details.

[`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php)
--------------------------

The SDK rejected a value without asking the API: a builder's [`build()`](../src/Builder/) called without a required field, a
payload the serializer can't write, a [`ValidatedBy`](../src/Validator/Rule/ValidatedBy.php) or [`Each`](../src/Validator/Rule/Each.php) that names the wrong kind of class, a
stored body or payload [`Api\SerializerInterface`](../src/Api/SerializerInterface.php) is asked to read and can't, or a webhook body
that isn't an order event. The last two are bad data; the rest mean the code is wrong.

[`ConnectionException`](../src/Exception/ConnectionException.php)
---------------------

The request never completed because of a DNS failure, refused connection, TLS failure or timeout.
The underlying PSR-18 [`Psr\Http\Client\ClientExceptionInterface`](https://www.php-fig.org/psr/psr-18/) is available
through [`Throwable::getPrevious()`](https://www.php.net/manual/en/throwable.getprevious.php).

> **You don't know whether the request was processed.** A timeout 30 seconds into `create_order`
> may mean the order was never created, or that it was created and the response was lost. See
> [retrying safely](#retrying-safely).

[`MalformedResponseException`](../src/Exception/MalformedResponseException.php)
----------------------------

The transport succeeded, but the body wasn't a Polkurier envelope or didn't have the shape the
endpoint returns. There are five causes, each with its own message:

| Cause | Message reads |
|-------|---------------|
| Body was not JSON | `Expected a JSON object from the Polkurier API, got body: "…"` |
| No `status` field | `The response has no "status" field, body: "…"` |
| Unrecognised status | `The response status "…" is unknown.` |
| Payload didn't map | `The payload could not be read as …\CreatedOrderMessage: …` |
| Payload was the wrong shape | `create_order answered with null where …\CreatedOrderMessage was expected.` |

The first four come from the serializer, and [`ResponseFactory`](../src/Http/Response/ResponseFactory.php) prefixes each with
`Polkurier API response (HTTP 502): `, naming the status the body arrived with.

The first two usually mean something else answered in place of the API, such as a proxy error page,
a captive portal or a WAF. The message quotes the raw body, truncated to 512 characters so a full
HTML error page doesn't end up in your logs.

The last two mean the API replied in its normal envelope, but the payload doesn't match what the
method returns.

An enum value the SDK doesn't know isn't treated as the wrong shape. See [enums](messages.md#enums).

```php
$e->getResponse();   // Psr\Http\Message\ResponseInterface: status line, headers, body
```

[`WebserviceException`](../src/Exception/WebserviceException.php)
---------------------

The API processed the request and refused it. The exception gives you the full exchange:

```php
$e->getErrorMessage();   // ErrorMessage: the parsed error
$e->getRequest();        // Psr\Http\Message\RequestInterface  (body contains the token)
$e->getResponse();       // Psr\Http\Message\ResponseInterface
```

[`ErrorMessage`](../src/Dto/Response/ErrorMessage.php) is the error form of the envelope:

```php
$error = $e->getErrorMessage();

$error->response;          // string: the human-readable message
$error->statusCode;        // ?int
$error->errorCode;         // ?string
$error->validationErrors;  // ?array<string, string[]>
$error->nonce;             // ?string: a rejected contact form's next nonce
```

> **Either code can be missing** from an error response, so both getters can return null.

[`ErrorMessage::$validationErrors`](../src/Dto/Response/ErrorMessage.php) is only set when the API rejects a payload during validation. The main
message only names the first failing field, but this array has every failure. It uses the same
shape as [`ValidationException`](../src/Exception/ValidationException.php), so you can handle both together:

```php
function fieldErrors(\Throwable $e): array
{
    return match (true) {
        $e instanceof ValidationException => $e->getValidationErrors(),
        $e instanceof WebserviceException => $e->getErrorMessage()->validationErrors ?? [],
        default => [],
    };
}
```

### The code enums

[`Enum\Http\ApiErrorCodeEnum`](../src/Enum/Http/ApiErrorCodeEnum.php) holds the 44 conditions the API is known to report. Each case's
value is the `error_code` string, [`ApiErrorCodeEnum::statusCode()`](../src/Enum/Http/ApiErrorCodeEnum.php) gives the numeric `status_code`,
and [`ApiErrorCodeEnum::fromStatusCode()`](../src/Enum/Http/ApiErrorCodeEnum.php) maps a number back. A few status codes stand for more
than one condition (the two-factor 506006, for one), and those map back to null, so the
`error_code` is the more precise of the two. Compared against the enum:

```php
use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;

$error = $e->getErrorMessage();
if ($error->errorCode === ApiErrorCodeEnum::TokenNotActive->value) { … }
if ($error->statusCode === ApiErrorCodeEnum::TokenNotActive->statusCode()) { … }
```

You compare against `->value` because the properties are plain strings and ints, not enums, just
like the order status. An unknown code simply won't match, and the response still maps.

[`AuthorizationException`](../src/Exception/AuthorizationException.php)
------------------------

A [`WebserviceException`](../src/Exception/WebserviceException.php) whose error code or status code says the credentials are the problem. They
may be missing, wrong or inactive, or sent with an unknown authorization type.

The SDK classifies it from whichever of the two codes the response has, since either can be
missing. The code set is public:

```php
AuthorizationException::ERROR_CODES;      // string[]
AuthorizationException::isAuthorizationErrorStatus($status);
AuthorizationException::isAuthorizationErrorCode($code);
```

Retrying won't help until the credentials change. It is a configuration problem, not a transient
fault.

---

Retrying safely
---------------

Which failures to retry:

```
ValidationException, InvalidArgumentException ... no: the payload is wrong
AuthorizationException .......................... no: raise an alert instead
MalformedResponseException ...................... rarely: maybe after a proxy glitch, never for
                                                  an unexpected payload
WebserviceException ............................. depends on the error code
ConnectionException
├── on a read ................................... yes
└── on a write .................................. only after checking it didn't go through
```

The risky case is a [`ConnectionException`](../src/Exception/ConnectionException.php) on a write. A write such as
[`OrdersServiceInterface::createOrder()`](../src/Api/Service/OrdersServiceInterface.php), [`MoneyBoxServiceInterface::createPayout()`](../src/Api/Service/MoneyBoxServiceInterface.php) or [`AddressesServiceInterface::saveAddress()`](../src/Api/Service/AddressesServiceInterface.php) without an id may already have gone through,
and the SDK can't tell you whether it did.

**There is no idempotency key.** A blind retry can create a second shipment. A safe retry first
looks for the order it tried to place, and sends it again only if it isn't there.

The reliable way is to give the order your own identifiers when you place it.
[`Order`](../src/Entity/Order.php) takes `externalApplicationId` (your application's UUID) and `externalOrderId`
(your id for the order), and the SDK refuses one without the other. Filter
[`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) by both to find the order. The match is exact:

```php
use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Exception\ConnectionException;

// $order was built with externalApplicationId: $myApplicationId, externalOrderId: $myOrderId
$message = new CreateOrderMessage(order: $order);

try {
    $created = $client->orders()->createOrder($message);
} catch (ConnectionException) {
    $existing = $client->orders()->getOrders(new GetOrdersMessage(
        externalApplicationId: $myApplicationId,
        externalOrderId: $myOrderId,
    ));

    if ($existing->totalRows === 0) {
        $created = $client->orders()->createOrder($message);
    }
}
```

An order placed without those identifiers can only be found by comparing. List the recent orders
for the carrier and look for one that matches:

```php
$recent = $client->orders()->getOrders(new GetOrdersMessage(
    courier: $order->getCourier(),
    dateFrom: new DateTimeImmutable('-10 minutes'),
    pageSize: 100,
));

$match = $this->findMatchingOrder($recent->result, $order);   // your comparison
```

The comparison is your own, since only you know what makes two of your orders the same. Usually
that's the recipient, weight and value. A false match means a shipment is never sent. A missed
match means a duplicate, which at least shows.

Payouts have nothing similar. Only [`$client->moneyBox()->getPayouts()`](../src/Api/Service/MoneyBoxServiceInterface.php) shows whether a payout went
through.

Logging a failure
-----------------

When `loggingEnabled` is on and you've passed a PSR-3 logger, the SDK logs errors itself: the
failing request, its body and the response, at `error` level with structured context. Secrets such
as the API token, personal data and file contents are left out unless you turn that off. See
[Configuration: logging](configuration.md#logging) and [secrets, personal data and files](configuration.md#secrets-personal-data-and-files).

If you log the exception yourself, note that [`WebserviceException::getRequest()`](../src/Exception/WebserviceException.php) returns a request
whose body contains the API token. [`WebserviceException::getErrorMessage()`](../src/Exception/WebserviceException.php) has the error without the request.
For the payload, [`SensitiveDataRedactor::message()`](../src/Log/SensitiveDataRedactor.php) gives a copy with the secrets hidden, and
[`PersonalDataRedactor::message()`](../src/Log/PersonalDataRedactor.php) one with the personal data hidden. Both together hide both:

```php
use Polkurier\Webservice\Log\PersonalDataRedactor;
use Polkurier\Webservice\Log\SensitiveDataRedactor;

$request = (new PersonalDataRedactor())->message((new SensitiveDataRedactor())->message($e->getRequest()));
```

Files in the body, such as a label, are left in.

A complete handler
------------------

```php
use Polkurier\Webservice\Exception as Polkurier;

try {
    $created = $client->orders()->createOrder($message);
} catch (Polkurier\ValidationException $e) {
    // Rejected locally, with nothing sent. The field errors are keyed by wire name.
    return $this->rejectWithFieldErrors($e->getValidationErrors());
} catch (Polkurier\AuthorizationException $e) {
    // A configuration problem. Retrying won't help until the credentials change.
    $this->alerts->credentialsRejected($e->getErrorMessage()->response);
    throw $e;
} catch (Polkurier\WebserviceException $e) {
    $error = $e->getErrorMessage();

    // Rejected by the API. A validation failure also has field errors.
    return $this->rejectWithFieldErrors(
        $error->validationErrors ?? ['_' => [$error->response]],
    );
} catch (Polkurier\ConnectionException $e) {
    // Unknown outcome: the order may or may not have been placed.
    $this->queue->reconcileLater($myOrderId);
    throw $e;
} catch (Polkurier\MalformedResponseException $e) {
    // Not an API reply. The body usually shows what answered.
    $this->logger->error('Unexpected reply', ['status' => $e->getResponse()->getStatusCode()]);
    throw $e;
}
```
