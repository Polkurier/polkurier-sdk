Architecture
============

The layout
----------

The source is organised by role, not by feature:

| Namespace | Holds |
|-----------|-------|
| `Api\Service` | One contract per service — what to type-hint |
| `Api\Validation` | The three contracts the validation layer is built on |
| `Api` | `ConfigInterface`, `ConfigProviderInterface`, `MessageInterface` |
| `Service` | The sixteen services, and `AbstractService` |
| `Request` | Everything you send |
| `Response` | Everything you get back |
| `Builder` | `AbstractBuilder` and the twenty-two hand-written builders |
| `Http` | The transport: client factory, request and response factories, the JSON envelope |
| `Serializer` | Serializer wiring, two custom normalizers, one attribute |
| `Validator` | The rules, and the machinery that runs them |
| `Enum` | Values the API accepts and returns |
| `Exception` | Everything the SDK throws |

`Config`, `ConfigProvider` and `PolkurierWebservice` sit at the root, since they are the wiring
rather than part of any layer.

`Api` is contracts only, and only the ones where supplying your own implementation does something.
The service interfaces double as the API reference. The three at its root are the cross-cutting
ones belonging to no single area.

The lifecycle of a call
-----------------------

```
$client->orders()->createOrder($message)
      │
      ├─ 1. PolkurierWebservice::service()      lazily build or reuse OrdersService
      │
      ├─ 2. OrdersService::createOrder()        pick the ApiMethodEnum and the response type
      │
      └─ 3. AbstractService::requestObject()
              │
              ├─ a. ValidatorInterface::validate()     ← ValidationException, nothing sent
              ├─ b. RequestFactoryInterface::create()  build the envelope, serialize it
              ├─ c. LoggerInterface                    if this method is in methodsToLog
              ├─ d. ClientInterface::sendRequest()     ← ConnectionException
              ├─ e. ResponseFactoryInterface::create() decode, read status, denormalize
              │                                        ← MalformedResponseException
              ├─ f. LoggerInterface                    on error, or if extended logging is on
              ├─ g. is it an ErrorMessage?             ← Authorization/WebserviceException
              └─ h. type-check the payload             ← MalformedResponseException
```

Seven collaborators, each behind an interface, each replaceable. Steps (a) through (h) are
`AbstractService`, which every service extends and which is the only place a request is actually
made.

### The five request helpers

A service method's whole job is choosing a method, a response shape and a payload.
`AbstractService` offers one protected helper per shape:

| Helper | Returns | For |
|--------|---------|-----|
| `requestObject()` | `T` | One object, guaranteed |
| `requestNullableObject()` | `?T` | One object, where `[]` genuinely means absent |
| `requestList()` | `list<T>` | A list; empty is a valid answer |
| `requestStringMap()` | `array<string, string>` | A map of plain strings |
| `requestRawArray()` | `array` | The two endpoints with no stable shape |
| `requestAcknowledgement()` | `bool` | Endpoints that only acknowledge |

So a service method is usually one line:

```php
public function createOrder(CreateOrderMessage $createOrderMessage): CreatedOrderMessage
{
    return $this->requestObject(
        ApiMethodEnum::CreateOrder,
        CreatedOrderMessage::class,
        $createOrderMessage,
    );
}
```

`requestNullableObject()` is used sparingly and deliberately: only where "nothing matched" is a
legitimate answer rather than a failure — a pickup point lookup against an unknown id, a
verification that has never been started. Everywhere else a missing object is a broken promise, not
an absence, and should raise.

`requestAcknowledgement()` exists because an acknowledgement carries no payload worth mapping.
Casting whatever is in `response` would risk reading an empty value as a failed call, so the
envelope's success status is the signal: the result is `true` unless the payload explicitly says
`false`.

Which classes are final
-----------------------

One rule decides it.

> **Infrastructure is final. Business logic is not.**

Break a piece of infrastructure and no call works at all, and the failure looks like the API
misbehaving rather than like your own change. So those are closed, and each implements an interface
instead — which is the sanctioned way to put something else in their place.

**Final:** `PolkurierWebservice`, `Config`, `ConfigProvider`, `ClientFactory`, `RequestFactory`,
`ResponseFactory`, `RequestMessage`, `ErrorMessage`, `ResponseWrapper`, `SerializerProvider`,
`SerializerConfig`, `OmitEmptyNormalizer`, `AbsentDateNormalizer`, `OmitWhenEmpty`.

**Open:** every service, every request and response class, every builder, and the whole validation
layer down to the individual rules. Getting one of those wrong costs you one call rather than all
of them.

Internals in the open classes are `protected` rather than `private`, so subclassing them is
actually possible rather than nominally possible.

Two exceptions worth knowing. `ResponseMessage` is not final because `ErrorMessage` extends it —
nothing else should. `AbstractBuilder::__construct()` is final while the class is not, which is
what makes `create()` safe to call on a subclass.

Extension points
----------------

Everything `PolkurierWebservice` uses is optional and constructor-injected:

```php
new PolkurierWebservice(
    config: $config,
    logger: $logger,
    serializerProvider: $serializerProvider,
    configProvider: $configProvider,
    clientFactory: $clientFactory,
    requestFactory: $requestFactory,
    responseFactory: $responseFactory,
    validator: $validator,
    services: [],
);
```

### `ClientFactoryInterface` — your own HTTP client

The most commonly useful one. The default builds Guzzle with the configured base URI, timeout,
`Content-Type: application/json`, and **redirects disabled** — the API is a single POST endpoint
that never legitimately redirects, so a 3xx means something is misconfigured and following it would
forward the credentials to wherever it points. Keep that behaviour in any replacement.

```php
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Psr\Http\Client\ClientInterface;

final readonly class MyClientFactory implements ClientFactoryInterface
{
    public function __construct(private ClientInterface $client) {}

    public function create(): ClientInterface
    {
        return $this->client;
    }
}
```

Any PSR-18 client works. Use this to add retries, circuit breaking, request tracing, a shared
connection pool, or your framework's own client so that its instrumentation applies.

### `SerializerProviderInterface` — mapping

See [Serialization — extending](serialization.md#extending-the-serializer).

### `ValidatorInterface` — payload rules

See [Validation — changing the rules](validation.md#changing-the-rules).

### `ConfigProviderInterface` — late or per-tenant configuration

See [Configuration — per-tenant credentials](configuration.md#per-tenant-credentials).

### `RequestFactoryInterface` / `ResponseFactoryInterface` — the envelope

Rarely needed; they exist so the envelope is not hard-coded into the services. A reason to replace
`RequestFactory`: adding a header to every request. A reason to replace `ResponseFactory`:
tolerating a status value the SDK does not recognise.

### `services:` — substituting a service

`PolkurierWebservice` builds its own services by calling the constructor they happen to share. That
is true of *them*, not of services in general — so it does not insist on it. Construct yours however
it needs to be constructed and hand it over:

```php
new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $myOrdersService],
);
```

The key is the class the instance stands in for; anything not given is built on first use, as
before. The value is not type-checked against the key, so you can pass anything satisfying the
interface the accessor returns.

This is how you add behaviour around one service without reimplementing the client:

```php
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;

final readonly class CachingOrders implements OrdersServiceInterface
{
    public function __construct(
        private OrdersServiceInterface $inner,
        private CacheInterface $cache,
    ) {}

    public function getStatus(GetStatusMessage $getStatusMessage): OrderStatusMessage
    {
        return $this->cache->get(
            'status-' . $getStatusMessage->getOrderNo(),
            fn() => $this->inner->getStatus($getStatusMessage),
        );
    }

    // … delegate the rest …
}
```

Or subclass the concrete service, which is shorter when you only want to change one method:

```php
final class RetryingOrders extends OrdersService
{
    public function createOrder(CreateOrderMessage $message): CreatedOrderMessage
    {
        // … your own retry policy around parent::createOrder() …
    }
}
```

Both are supported. The interface route is better when you are composing; the subclass route is
better when you are adjusting.

Not using the facade at all
---------------------------

`PolkurierWebservice` is a convenience. Nothing depends on it, and a container-driven application
usually should not use it — it hides the wiring the container is there to do.

Wire a service directly:

```php
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\OrdersService;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

$configProvider = new ConfigProvider($config);
$serializer     = new SerializerProvider();

$orders = new OrdersService(
    requestFactory:  new RequestFactory($serializer, $configProvider),
    responseFactory: new ResponseFactory($serializer),
    logger:          $logger,
    clientFactory:   new ClientFactory($configProvider),
    configProvider:  $configProvider,
    validator:       DefaultValidatorRegistryFactory::create(),
);

$orders->getOrders($filter);
```

Every service takes those same six arguments, in that order. Note the default for `validator` on
`AbstractService` is `NullValidator` — a service constructed by hand validates nothing unless you
pass a validator, which is the opposite of the facade's default. Pass
`DefaultValidatorRegistryFactory::create()` unless you mean not to.

[Integrations](integrations/README.md) shows this wiring in each major framework's own idiom.

Why there are no interfaces on models
-------------------------------------

Request and response classes have none, deliberately. Their wire names, formats and dates live in
serializer attributes on the classes themselves, so a substitute implementation would not
serialize. An interface there advertises a swap that cannot work.

The same reasoning applies in reverse to `Api`: the interfaces that exist are exactly the ones
where an alternative implementation does something useful.

Thread-safety and reuse
-----------------------

The SDK holds no connection state and performs no I/O until a service method is called.

- `PolkurierWebservice` caches its services after first use, so asking for one repeatedly is cheap.
- `AbstractService` and `ClientFactory` each resolve configuration once, in their constructors.
- `SerializerProvider` holds no per-call state and can be shared across any number of clients.
- The reflection caches in `AttributeValidator`, `MessageFields`, `OmitEmptyNormalizer` and
  `AbstractBuilder` are `static` and keyed by class name, so they warm once per process.

`AbstractService::$lastHttpResponse` is the one piece of mutable per-call state, and it exists only
so the exception factories can report what arrived. In a shared-nothing PHP process this is
unremarkable; under a long-running worker, do not share one service instance across concurrent
coroutines.
