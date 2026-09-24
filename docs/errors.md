Errors
======

The first thing to know
-----------------------

**The API answers HTTP 200 for everything.** A bad token is a 200. A rejected payload is a 200. A
nonexistent order is a 200. Never branch on the transport status; the SDK does not, and nothing
above it should.

The real outcome is the envelope's `status` field. The SDK reads it, and turns the failure cases
into exceptions rather than into return values — because a method that promises an `Order` cannot
also hand back an error and stay honest about its type.

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

Catch `Exception` to handle any SDK failure in one place; catch a subclass to distinguish.

| Exception | Raised when | Was anything sent? | Retry? |
|-----------|-------------|--------------------|--------|
| `ValidationException` | A payload was rejected before sending | No | Not without changing it |
| `InvalidArgumentException` | A message was constructed with values the SDK rejects | No | No |
| `ConnectionException` | The request never completed | **Unknown** | Maybe — see below |
| `MalformedResponseException` | The reply was not an API envelope, or not the promised shape | Yes | Rarely helps |
| `AuthorizationException` | Credentials missing, wrong, or inactive | Yes | No |
| `WebserviceException` | Any other error the API reported | Yes | Depends on the error |

Order your catches accordingly — `AuthorizationException` before `WebserviceException`,
`ValidationException` before `InvalidArgumentException`.

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
    // Credentials. Re-authenticate; retrying as-is will not help.
} catch (WebserviceException $e) {
    // The API refused, and said why.
} catch (ConnectionException $e) {
    // Never got an answer. The order may or may not exist.
} catch (MalformedResponseException $e) {
    // Something between you and the API is not the API.
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

Field names are the API's own, and the shape is the same one the API uses for its own rejections,
so a single presentation path serves both. Full treatment in [Validation](validation.md).

`InvalidArgumentException`
--------------------------

A message was constructed with values the SDK rejects outright — a list holding the wrong type, a
`ValidatedBy` naming something that is not a validator, a builder asked to `build()` without a
required field. These are programming errors, not runtime conditions: they indicate the code is
wrong, not the data.

`ConnectionException`
---------------------

The request never completed: DNS failure, connection refused, TLS failure, timeout. Wraps the
underlying PSR-18 `ClientExceptionInterface` as `$e->getPrevious()`, so the specific cause is still
available.

> **You do not know whether the request was processed.** A timeout thirty seconds into
> `create_order` may mean the order was never created, or that it was created and the answer was
> lost. See [retrying safely](#retrying-safely).

`MalformedResponseException`
----------------------------

The transport succeeded but the body was not a Polkurier envelope, or not the shape the endpoint
promises. Four distinct causes, each with its own message:

| Cause | Message reads |
|-------|---------------|
| Body was not JSON | `Expected a JSON object from the Polkurier API, got HTTP 502 with body: "…"` |
| No `status` field | `Polkurier API response (HTTP 200) has no "status" field, body: "…"` |
| Unrecognised status | `Unknown Polkurier API response status: "…"` |
| Payload was the wrong shape | `create_order answered with array where …\CreatedOrderMessage was expected.` |

The first two usually mean something between you and the API answered instead of it: a proxy error
page, a captive portal, a WAF. The raw body is quoted, truncated to 512 characters so an HTML error
page does not land whole in your logs.

The fourth means the API answered, in its own envelope, with a payload that is not what the method
promised. That is a real incompatibility — raised rather than returned so that a typed accessor
keeps its type.

```php
$e->getResponse();   // Psr\Http\Message\ResponseInterface — status line, headers, body
```

`WebserviceException`
---------------------

The API processed the request and refused it. Carries everything about the exchange:

```php
$e->getErrorMessage();   // ErrorMessage — the parsed error
$e->getRequest();        // Psr\Http\Message\RequestInterface  (body contains the token)
$e->getResponse();       // Psr\Http\Message\ResponseInterface
```

`ErrorMessage` is the envelope's error form:

```php
$error = $e->getErrorMessage();

$error->getResponse();          // string — the human-readable message
$error->getStatusCode();        // ?int
$error->getErrorCode();         // ?string
$error->getValidationErrors();  // ?array<string, string[]>
```

> **Both codes are nullable, and you must treat them that way.** Either can be absent from an
> error response, so `$error->getStatusCode()` dereferenced unconditionally will fatal. Check for
> null, or compare with `?->`.

`getValidationErrors()` is present only on validation failures from the service, and it is the most
useful part of one: the main message names just the first failing field, while this carries every
failure at once — in exactly the shape `ValidationException` uses, so:

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
send — 35 each, the same conditions expressed numerically and as strings. Compare against them
rather than against literals:

```php
use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;

if ($e->getErrorMessage()->getErrorCode() === ApiErrorCodeEnum::TokenNotActive->value) { … }
```

They are compared by `->value` rather than being typed onto the property for the same reason order
status is a string: an unrecognised code should be a comparison that does not match, not a response
that fails to map.

`AuthorizationException`
------------------------

A `WebserviceException` whose error code or status code says the credentials are the problem:
missing, malformed, unknown, inactive, or used with an authorization method the account may not
use.

Classified from whichever of the two codes the response carried, since either may be absent. The
sets are public:

```php
AuthorizationException::ERROR_STATUSES;   // int[]
AuthorizationException::ERROR_CODES;      // string[]
AuthorizationException::isAuthorizationErrorStatus($status);
AuthorizationException::isAuthorizationErrorCode($code);
```

Retrying will not help; the credentials have to change. Treat it as a configuration alarm, not as a
transient fault.

---

Retrying safely
---------------

Which failures are worth retrying, and which are not:

| Exception | Retry |
|-----------|-------|
| `ValidationException`, `InvalidArgumentException` | No. The payload is wrong. |
| `AuthorizationException` | No. Alarm instead. |
| `MalformedResponseException` | Rarely. A proxy hiccup, maybe; an unexpected payload, no. |
| `ConnectionException` | **Only for reads, unless you can check first.** |
| `WebserviceException` | Depends entirely on the error code. |

The dangerous case is a `ConnectionException` on a write. A read can be retried freely. A write —
`createOrder()`, `createPayout()`, `saveAddress()` without an id — may already have happened, and
the SDK cannot tell you which.

**There is no idempotency key.** `CreateOrderMessage` has no field for one. `GetOrdersMessage`
can *filter* on `externalApplicationId` and `externalOrderId`, and `Order` carries them back, but
`createOrder()` has nowhere to set them. So a blind retry risks a second shipment, and nothing
stops it.

What you can do instead is reconcile before retrying — query for an order that matches what you
were about to place, and only send again if there is none:

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

The comparison is yours to write, because only you know what makes two of your orders the same —
recipient and weight and value, usually. Make it strict: a false match loses a shipment, a missed
match duplicates one, and of the two the second is at least visible.

Where those external identifiers are present on your orders, the reconciliation becomes exact
rather than heuristic:

```php
$existing = $client->orders()->getOrders(new GetOrdersMessage(
    externalApplicationId: $myApplicationId,
    externalOrderId: $myOrderId,
));
```

For payouts, there is no equivalent at all — check `moneyBox()->getPayouts()` before retrying, and
prefer letting a human decide.

Logging a failure
-----------------

The SDK logs errors itself when `loggingEnabled` is on and a PSR-3 logger was given: the failing
request, its body, and what came back, at `error` level with structured context. See
[Configuration — logging](configuration.md#logging), including the warning about tokens in request
bodies.

If you log the exception yourself, `WebserviceException::getRequest()` returns a request whose body
contains the API token. Log `$e->getErrorMessage()` rather than the request unless you specifically
need the payload.

A complete handler
------------------

```php
use Polkurier\Webservice\Exception as Polkurier;

try {
    $created = $client->orders()->createOrder($order);
} catch (Polkurier\ValidationException $e) {
    // Ours. Show the fields to whoever filled the form in.
    return $this->rejectWithFieldErrors($e->getValidationErrors());
} catch (Polkurier\AuthorizationException $e) {
    // Configuration. Page someone; do not retry.
    $this->alerts->credentialsRejected($e->getErrorMessage()->getResponse());
    throw $e;
} catch (Polkurier\WebserviceException $e) {
    $error = $e->getErrorMessage();

    // Theirs, and they said why. Field errors if it was a validation failure.
    return $this->rejectWithFieldErrors(
        $error->getValidationErrors() ?? ['_' => [$error->getResponse()]],
    );
} catch (Polkurier\ConnectionException $e) {
    // Unknown outcome. Reconcile rather than guess.
    $this->queue->reconcileLater($myOrderId);
    throw $e;
} catch (Polkurier\MalformedResponseException $e) {
    // Not the API. Capture the body; it usually names the thing that answered.
    $this->logger->error('Unexpected reply', ['status' => $e->getResponse()->getStatusCode()]);
    throw $e;
}
```
