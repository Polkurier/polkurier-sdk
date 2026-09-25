Errors
======

HTTP status is always 200
-------------------------

**The API answers HTTP 200 for everything.** A bad token, a rejected payload and a nonexistent
order all come back as 200. Don't branch on the transport status. The SDK doesn't, and your code
shouldn't either.

The actual outcome is in the envelope's `status` field. The SDK reads it and throws an exception
for each failure case. A method that returns an `Order` either returns one or throws.

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

Catch `Exception` to handle any SDK failure in one place, or catch a subclass to tell them apart.

| Exception | Raised when | Was anything sent? | Retry? |
|-----------|-------------|--------------------|--------|
| `ValidationException` | A payload was rejected before sending | No | Not without changing it |
| `InvalidArgumentException` | A message was constructed with values the SDK rejects | No | No |
| `ConnectionException` | The request never completed | **Unknown** | Maybe, see below |
| `MalformedResponseException` | The reply was not an API envelope, or not the promised shape | Yes | Rarely helps |
| `AuthorizationException` | Credentials missing, wrong, or inactive | Yes | No |
| `WebserviceException` | Any other error the API reported | Yes | Depends on the error |

Put the subclasses first in your catch blocks: `AuthorizationException` before
`WebserviceException`, and `ValidationException` before `InvalidArgumentException`.

```php
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

try {
    $created = $client->orders()->createOrder($order);
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

`ValidationException`
---------------------

The payload was rejected locally. **Nothing was sent and nothing changed.**

```php
$e->getValidationErrors();              // array<string, string[]>, keyed by wire field name
$e->getFieldErrors('sender.postcode');  // string[], empty when that field passed
$e->getMessage();                       // every failure, flattened into one sentence
```

Field names are the API's own, and the array has the same shape the API uses for its own
validation errors, so you can display both the same way. See [Validation](validation.md) for
details.

`InvalidArgumentException`
--------------------------

A message was constructed with values the SDK rejects outright. Examples: a list holding the wrong
type, a `ValidatedBy` that names something that isn't a validator, or a builder's `build()` called
without a required field. These are programming errors. They mean the code is wrong, not the data.

`ConnectionException`
---------------------

The request never completed because of a DNS failure, refused connection, TLS failure or timeout.
The underlying PSR-18 `ClientExceptionInterface` is available through `$e->getPrevious()`.

> **You don't know whether the request was processed.** A timeout 30 seconds into `create_order`
> may mean the order was never created, or that it was created and the response was lost. See
> [retrying safely](#retrying-safely).

`MalformedResponseException`
----------------------------

The transport succeeded, but the body wasn't a Polkurier envelope or didn't have the shape the
endpoint returns. There are four causes, each with its own message:

| Cause | Message reads |
|-------|---------------|
| Body was not JSON | `Expected a JSON object from the Polkurier API, got HTTP 502 with body: "…"` |
| No `status` field | `Polkurier API response (HTTP 200) has no "status" field, body: "…"` |
| Unrecognised status | `Unknown Polkurier API response status: "…"` |
| Payload was the wrong shape | `create_order answered with array where …\CreatedOrderMessage was expected.` |

The first two usually mean something else answered in place of the API, such as a proxy error page,
a captive portal or a WAF. The message quotes the raw body, truncated to 512 characters so a full
HTML error page doesn't end up in your logs.

The fourth means the API replied in its normal envelope, but the payload doesn't match what the
method returns. This is a real incompatibility. The SDK throws so that typed methods never return
the wrong type.

```php
$e->getResponse();   // Psr\Http\Message\ResponseInterface: status line, headers, body
```

`WebserviceException`
---------------------

The API processed the request and refused it. The exception gives you the full exchange:

```php
$e->getErrorMessage();   // ErrorMessage: the parsed error
$e->getRequest();        // Psr\Http\Message\RequestInterface  (body contains the token)
$e->getResponse();       // Psr\Http\Message\ResponseInterface
```

`ErrorMessage` is the error form of the envelope:

```php
$error = $e->getErrorMessage();

$error->getResponse();          // string: the human-readable message
$error->getStatusCode();        // ?int
$error->getErrorCode();         // ?string
$error->getValidationErrors();  // ?array<string, string[]>
```

> **Both codes are nullable, so treat them that way.** Either one can be missing from an error
> response. Calling a method on the result of `$error->getStatusCode()` without a null check is a
> fatal error. Check for null, or use `?->`.

`getValidationErrors()` is only set when the API rejects a payload during validation. The main
message only names the first failing field, but this array has every failure. It uses the same
shape as `ValidationException`, so you can handle both together:

```php
function fieldErrors(\Throwable $e): array
{
    return match (true) {
        $e instanceof ValidationException => $e->getValidationErrors(),
        $e instanceof WebserviceException => $e->getErrorMessage()->getValidationErrors() ?? [],
        default => [],
    };
}
```

### The code enums

`Enum\Http\ApiStatusCodeEnum` and `Enum\Http\ApiErrorCodeEnum` hold the values the API is known to
send. There are 35 of each, covering the same conditions as numbers and as strings. Compare
against them instead of literals:

```php
use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;

if ($e->getErrorMessage()->getErrorCode() === ApiErrorCodeEnum::TokenNotActive->value) { … }
```

You compare against `->value` because the properties are plain strings and ints, not enums, just
like the order status. An unknown code simply won't match, and the response still maps.

`AuthorizationException`
------------------------

A `WebserviceException` whose error code or status code says the credentials are the problem. They
may be missing, malformed, unknown or inactive, or used with an authorization method the account
isn't allowed to use.

The SDK classifies it from whichever of the two codes the response has, since either can be
missing. The code sets are public:

```php
AuthorizationException::ERROR_STATUSES;   // int[]
AuthorizationException::ERROR_CODES;      // string[]
AuthorizationException::isAuthorizationErrorStatus($status);
AuthorizationException::isAuthorizationErrorCode($code);
```

Retrying won't help until the credentials change. Treat it as a configuration problem that needs
an alert, not as a transient fault.

---

Retrying safely
---------------

Which failures to retry:

| Exception | Retry |
|-----------|-------|
| `ValidationException`, `InvalidArgumentException` | No. The payload is wrong. |
| `AuthorizationException` | No. Raise an alert instead. |
| `MalformedResponseException` | Rarely. Maybe after a proxy glitch, never for an unexpected payload. |
| `ConnectionException` | **Only for reads, unless you can check first.** |
| `WebserviceException` | Depends on the error code. |

The risky case is a `ConnectionException` on a write. You can retry reads freely. A write such as
`createOrder()`, `createPayout()` or `saveAddress()` without an id may already have gone through,
and the SDK can't tell you whether it did.

**There is no idempotency key.** `CreateOrderMessage` has no field for one. `GetOrdersMessage` can
*filter* on `externalApplicationId` and `externalOrderId`, and `Order` returns them, but
`createOrder()` has no way to set them. A blind retry can create a second shipment, and nothing
will stop it.

Instead, reconcile before retrying. Look for an order that matches the one you tried to place, and
only send it again if you don't find one:

```php
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

try {
    $created = $client->orders()->createOrder($order);
} catch (ConnectionException) {
    $recent = $client->orders()->getOrders(new GetOrdersMessage(
        courier: $order->getCourier(),
        dateFrom: new DateTimeImmutable('-10 minutes'),
        pageSize: 100,
    ));

    $match = $this->findMatchingOrder($recent->getResult(), $order);   // your comparison

    $created = $match ?? $client->orders()->createOrder($order);
}
```

You have to write the comparison yourself, since only you know what makes two of your orders the
same. Usually that's the recipient, weight and value. Keep it strict. A false match means a
shipment is never sent. A missed match means a duplicate, which at least you'll notice.

If your orders have those external identifiers, you can look the order up directly:

```php
$existing = $client->orders()->getOrders(new GetOrdersMessage(
    externalApplicationId: $myApplicationId,
    externalOrderId: $myOrderId,
));
```

Payouts have nothing similar. Check `moneyBox()->getPayouts()` before retrying, and preferably
have a person make the call.

Logging a failure
-----------------

When `loggingEnabled` is on and you've passed a PSR-3 logger, the SDK logs errors itself: the
failing request, its body and the response, at `error` level with structured context. See
[Configuration: logging](configuration.md#logging), including the warning about tokens in request
bodies.

If you log the exception yourself, note that `WebserviceException::getRequest()` returns a request
whose body contains the API token. Log `$e->getErrorMessage()` instead, unless you need the
payload.

A complete handler
------------------

```php
use Polkurier\Webservice\Exception as Polkurier;

try {
    $created = $client->orders()->createOrder($order);
} catch (Polkurier\ValidationException $e) {
    // Rejected locally. Show the field errors to whoever filled in the form.
    return $this->rejectWithFieldErrors($e->getValidationErrors());
} catch (Polkurier\AuthorizationException $e) {
    // Configuration problem. Alert someone, don't retry.
    $this->alerts->credentialsRejected($e->getErrorMessage()->getResponse());
    throw $e;
} catch (Polkurier\WebserviceException $e) {
    $error = $e->getErrorMessage();

    // Rejected by the API. Use the field errors if it was a validation failure.
    return $this->rejectWithFieldErrors(
        $error->getValidationErrors() ?? ['_' => [$error->getResponse()]],
    );
} catch (Polkurier\ConnectionException $e) {
    // Unknown outcome. Reconcile later instead of guessing.
    $this->queue->reconcileLater($myOrderId);
    throw $e;
} catch (Polkurier\MalformedResponseException $e) {
    // Not an API reply. The body usually shows what answered.
    $this->logger->error('Unexpected reply', ['status' => $e->getResponse()->getStatusCode()]);
    throw $e;
}
```
