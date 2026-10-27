Architecture
============

The layout
----------

The source is organised by role:

| Namespace | Holds |
|-----------|-------|
| [`Api\Service`](../src/Api/Service/) | One contract per service. Type-hint these |
| [`Api\Validation`](../src/Api/Validation/) | The 3 contracts the validation layer is built on |
| `Api` | [`ConfigInterface`](../src/Api/ConfigInterface.php), [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php), [`MessageInterface`](../src/Api/MessageInterface.php) |
| `Service` | The 16 services, and [`AbstractService`](../src/Service/AbstractService.php) |
| `Request` | Everything you send |
| `Response` | Everything you get back |
| `Builder` | [`AbstractBuilder`](../src/Builder/AbstractBuilder.php) and the builders |
| `Http` | The transport: client factory, request and response factories, JSON envelope |
| `Serializer` | Serializer wiring, the SDK's own normalizers and 2 attributes |
| `Log` | Preparing request and response bodies for the log |
| `Webhook` | [`OrderWebhookReader`](../src/Webhook/OrderWebhookReader.php), for the order events Polkurier posts to your system |
| `Testing` | [`RecordingClient`](../src/Testing/RecordingClient.php), a fake transport for your tests |
| `Validator` | The rules, and the machinery that runs them |
| `Enum` | Values the API accepts and returns |
| [`Exception`](../src/Exception/Exception.php) | Everything the SDK throws |

[`Config`](../src/Config.php), [`ConfigProvider`](../src/ConfigProvider.php) and [`PolkurierWebservice`](../src/PolkurierWebservice.php) sit at the root. They wire the layers together
and do not belong to any one of them.

`Api` holds only contracts, and only those where your own implementation can do something useful.
The service interfaces also serve as the API reference. The 3 interfaces at the root of `Api` are
shared across areas.

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
              ├─ e. ResponseFactoryInterface::create() decode, read status, denormalize,
              │                                        note enum values the SDK doesn't know
              │                                        ← MalformedResponseException
              ├─ f. LoggerInterface                    on error, or if extended logging is on,
              │                                        and a warning per unknown enum value
              ├─ g. is it an ErrorMessage?             ← Authorization/WebserviceException
              └─ h. type-check the payload             ← MalformedResponseException
```

There are 7 collaborators, each behind an interface you can replace. Steps (a) to (h) happen in
[`AbstractService`](../src/Service/AbstractService.php). Every service extends it, and it is the only place a request is sent.

Two more objects take part. Before anything is logged, [`LogSanitizer`](../src/Log/LogSanitizer.php) prepares each body as the
configuration says. After (e), the HTTP response and the unknown enum values go into a
[`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php), which [`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php) and
[`PolkurierWebservice::getLastUnknownEnumValues()`](../src/PolkurierWebservice.php) read.

### The request helpers

A service method picks an API method, a response shape and a payload. [`AbstractService`](../src/Service/AbstractService.php) has one
protected helper per shape:

| Helper | Returns | For |
|--------|---------|-----|
| [`AbstractService::requestObject()`](../src/Service/AbstractService.php) | `T` | One object, guaranteed |
| [`AbstractService::requestNullableObject()`](../src/Service/AbstractService.php) | `?T` | One object, where `[]` means absent |
| [`AbstractService::requestList()`](../src/Service/AbstractService.php) | `list<T>` | A list. Empty is a valid answer |
| [`AbstractService::requestStringMap()`](../src/Service/AbstractService.php) | `array<string, string>` | A map of plain strings |
| [`AbstractService::requestRawArray()`](../src/Service/AbstractService.php) | `array` | A response with no fixed structure, as decoded JSON |
| [`AbstractService::requestAcknowledgement()`](../src/Service/AbstractService.php) | `bool` | Endpoints that only acknowledge |

So a service method is usually a single statement:

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

[`AbstractService::requestNullableObject()`](../src/Service/AbstractService.php) is only used where "nothing matched" is a valid answer, e.g. looking up a
pickup point by an unknown id, or a verification that was never started. Everywhere else a missing
object is an error and throws.

[`AbstractService::requestAcknowledgement()`](../src/Service/AbstractService.php) is for calls whose payload has nothing to map. Casting whatever is in
`response` could read an empty value as a failure, so the envelope's success status is used
instead. The result is `true` unless the payload explicitly says `false`.

Which classes are final
-----------------------

**Final:** [`PolkurierWebservice`](../src/PolkurierWebservice.php), and the JSON envelope: [`RequestMessage`](../src/Http/Request/RequestMessage.php), [`ErrorMessage`](../src/Http/Response/ErrorMessage.php) and
[`ResponseWrapper`](../src/Http/Response/ResponseWrapper.php). So are the serializer attributes [`OmitWhenEmpty`](../src/Serializer/Attribute/OmitWhenEmpty.php) and
[`InlineFields`](../src/Serializer/Attribute/InlineFields.php), the test double [`RecordingClient`](../src/Testing/RecordingClient.php), and [`LogJson`](../src/Log/LogJson.php), a small helper.

**Open:** everything else. That covers [`Config`](../src/Config.php) and [`ConfigProvider`](../src/ConfigProvider.php), the serializer wiring
([`SerializerProvider`](../src/Serializer/SerializerProvider.php), [`SerializerConfig`](../src/Serializer/SerializerConfig.php) and the normalizers), the log preparation
([`LogSanitizer`](../src/Log/LogSanitizer.php) and the redactors), the three factories
([`ClientFactory`](../src/Http/ClientFactory.php), [`RequestFactory`](../src/Http/Request/RequestFactory.php), [`ResponseFactory`](../src/Http/Response/ResponseFactory.php)), every service, request and response class and
builder, and the validation layer down to the individual rules. Subclass one to change a single
step, or implement its interface (where it has one) to replace it outright. A broken change to the
configuration, the serializer or a factory affects every call and tends to look like an API
problem.

Most internals in the open classes are `protected`, so you can override them in a subclass.

[`AbstractBuilder::__construct()`](../src/Builder/AbstractBuilder.php) takes no arguments, and a subclass that declares its own must keep it that
way, so [`AbstractBuilder::create()`](../src/Builder/AbstractBuilder.php) is safe to call on a subclass.

Extension points
----------------

Every collaborator of [`PolkurierWebservice`](../src/PolkurierWebservice.php) is optional and passed through the constructor:

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

### `ClientFactoryInterface`: your own HTTP client

The default builds a Guzzle client with the configured
base URI, timeout and **redirects disabled**. The API is a single
POST endpoint that never redirects, so a 3xx means something is misconfigured, and following it
would send your credentials to wherever it points. A replacement client that follows redirects
would do the same.

A replacement client also has to supply the base URI, because requests carry only the path `/`. It
must keep the request's `Content-Type: application/json`: the API answers any other value, a charset
parameter included, with UNSUPPORTED_MEDIA_TYPE.

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

Any PSR-18 client works. A replacement can add retries, circuit breaking, request tracing or a
shared connection pool, or be your framework's client so its instrumentation applies.

### `SerializerProviderInterface`: mapping

See [Serialization: extending the serializer](serialization.md#extending-the-serializer).

### `ValidatorInterface`: payload rules

See [Validation: changing the rules](validation.md#changing-the-rules).

### `ConfigProviderInterface`: late or per-tenant configuration

See [Configuration: per-tenant credentials](configuration.md#per-tenant-credentials).

### `RequestFactoryInterface` and `ResponseFactoryInterface`: the envelope

They keep the envelope format out of the services. You might replace
[`RequestFactory`](../src/Http/Request/RequestFactory.php) to add a header to every request, or [`ResponseFactory`](../src/Http/Response/ResponseFactory.php) to accept a status value
the SDK does not recognise.

### Substituting a service

[`PolkurierWebservice`](../src/PolkurierWebservice.php) builds its own services through the constructor they share. Your service does
not need that constructor. Build it however you like and pass it in with `services:`:

```php
new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $myOrdersService],
);
```

The key is the class your instance replaces. Services you do not pass are built on first use as
usual. The value is not type-checked against the key, so anything that implements the interface
the accessor returns will do.

This adds behaviour around one service without reimplementing the client:

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

Or subclass the concrete service. That is shorter when you only want to change one method:

```php
final class RetryingOrders extends OrdersService
{
    public function createOrder(CreateOrderMessage $message): CreatedOrderMessage
    {
        // … your own retry policy around parent::createOrder() …
    }
}
```

Implementing the interface suits wrapping and composing. Subclassing suits
small changes.

Not using the facade at all
---------------------------

[`PolkurierWebservice`](../src/PolkurierWebservice.php) is a convenience and nothing depends on it. In an application with a DI
container, it duplicates the wiring the container does. A service can be wired directly:

```php
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\OrdersService;

$configProvider = new ConfigProvider($config);
$serializer     = new SerializerProvider();

$orders = new OrdersService(
    requestFactory:  new RequestFactory($serializer, $configProvider),
    responseFactory: new ResponseFactory($serializer),
    logger:          $logger,
    clientFactory:   new ClientFactory($configProvider),
    configProvider:  $configProvider,
);

$orders->getOrders($filter);
```

Every service takes the same arguments in the same order. The 5 above are the ones to pass. Three
more are optional: `validator`, by default the facade's, from [`DefaultValidatorRegistryFactory::create()`](../src/Validator/DefaultValidatorRegistryFactory.php)
([`NullValidator`](../src/Validator/NullValidator.php) turns the checks off); `lastResponse`, a [`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php) that keeps the last
response; and `logSanitizer`, the [`LogSanitizer`](../src/Log/LogSanitizer.php) that prepares bodies for the log. Pass one
[`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php) to several services if you want to see the last call made through any of
them.

[Integrations](integrations/README.md) shows this wiring for each major framework.

Thread-safety and reuse
-----------------------

The SDK does no I/O until you call a service method.

- [`PolkurierWebservice`](../src/PolkurierWebservice.php) caches each service after first use, so repeated calls to an accessor
  return the same instance.
- The configuration is read from the provider on every call and not kept.
- [`ClientFactory`](../src/Http/ClientFactory.php) builds one HTTP client per API URL and timeout, and reuses it.
- [`SerializerProvider`](../src/Serializer/SerializerProvider.php) holds no per-call state and can be shared across any number of clients.
- What the SDK reads from its classes by reflection is cached per class, once per process.

Each call replaces the last response a service keeps, and the
[`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php) the facade's services share. In a normal shared-nothing PHP process this
does not matter. In a long-running worker, concurrent coroutines that share one client or service
instance overwrite this state for each other.
