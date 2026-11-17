Architecture
============

The layout
----------

The source is organised by role:

| Namespace | Holds |
|-----------|-------|
| [`Api\Service`](../src/Api/Service/) | One contract per service. Type-hint these |
| [`Api\Validation`](../src/Api/Validation/) | [`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php), which every payload passes before it is sent |
| `Api` | [`ConfigInterface`](../src/Api/ConfigInterface.php), [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php), [`MessageInterface`](../src/Api/MessageInterface.php), [`ParsedResponseInterface`](../src/Api/ParsedResponseInterface.php), [`SerializerInterface`](../src/Api/SerializerInterface.php), [`SerializerConfigInterface`](../src/Api/SerializerConfigInterface.php) |
| `Service` | The 16 services, and [`AbstractService`](../src/Service/AbstractService.php) |
| `Dto\Request` | What you send, apart from stored records |
| `Dto\Response` | What you get back, apart from stored records |
| `Entity` | Records Polkurier stores, one class each for sending and reading: address, bank account, phone number, pack template, and the order with its addresses, packs, cash on delivery and collection |
| `Builder` | [`AbstractBuilder`](../src/Builder/AbstractBuilder.php), with [`Builder\Entity`](../src/Builder/Entity/) and [`Builder\Request`](../src/Builder/Request/) mirroring what they build |
| `Http` | The transport: client factory, request and response factories, JSON envelope |
| `Serializer` | Serializer wiring, the SDK's own normalizers and 6 attributes, and three contract types: [`ReadResponse`](../src/Serializer/ReadResponse.php), [`ResponseDrift`](../src/Serializer/ResponseDrift.php), [`SerializerConfig`](../src/Serializer/SerializerConfig.php) |
| `Log` | Preparing request and response bodies for the log |
| `Webhook` | [`OrderReader`](../src/Webhook/OrderReader.php), for the order events Polkurier posts to your system, and the [`ReadEvent`](../src/Webhook/ReadEvent.php) it answers with |
| `Testing` | [`RecordingClient`](../src/Testing/RecordingClient.php), a fake transport for your tests |
| `Validator` | The rules, and the machinery that runs them |
| `Enum` | Values the API accepts and returns |
| [`Exception`](../src/Exception/Exception.php) | Everything the SDK throws |

[`Config`](../src/Config.php), [`ConfigProvider`](../src/ConfigProvider.php) and [`PolkurierWebservice`](../src/PolkurierWebservice.php) sit at the root. They wire the layers together
and do not belong to any one of them.

`Api` holds only contracts, and only those where your own implementation can do something useful.
[`SerializerInterface`](../src/Api/SerializerInterface.php) is the one to be received rather than implemented: the SDK hands one
out and never takes one. The service interfaces also serve as the API reference. The 6 interfaces
at the root of `Api` are shared across areas.

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
              │                                        note how the response differs from the SDK
              │                                        ← MalformedResponseException
              ├─ f. LoggerInterface                    on error, or if extended logging is on,
              │                                        and a warning per difference
              ├─ g. is it an ErrorMessage?             ← Authorization/WebserviceException
              └─ h. type-check the payload             ← MalformedResponseException
```

There are 6 collaborators, each behind an interface. Steps (a) to (h) happen in
[`AbstractService`](../src/Service/AbstractService.php). Every service extends it, and it is the only place a request is sent. Four of
them you replace through the client: the logger, [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php),
[`ClientFactoryInterface`](../src/Api/Http/ClientFactoryInterface.php) and [`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php). The request factory and the response factory are
arguments a service is constructed with, so replacing one means building that service yourself
([Extension points](#extension-points)). The serializer those two read and write through,
[`MessageSerializer`](../src/Serializer/MessageSerializer.php), is not among them: [`PolkurierWebservice::serializer()`](../src/PolkurierWebservice.php) hands out the
instance they use, typed as [`Api\SerializerInterface`](../src/Api/SerializerInterface.php), and takes none in return, so an
application sets serialization only through [`Api\SerializerConfigInterface`](../src/Api/SerializerConfigInterface.php)
([Serialization](serialization.md#configuring-the-serializer)).

Two more objects take part. Before anything is logged, [`LogSanitizer`](../src/Log/LogSanitizer.php) prepares each body as the
configuration says. After (e), the HTTP response and its differences from the SDK go into a
[`LastResponseHolder`](../src/Http/Response/LastResponseHolder.php), which [`PolkurierWebservice::getLastResponse()`](../src/PolkurierWebservice.php) and
[`PolkurierWebservice::getLastResponseDrift()`](../src/PolkurierWebservice.php) read.

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

What a release can change
-------------------------

Every type the SDK promises carries `@api`, and nothing else does. A minor or patch release doesn't
break code that relies only on:

- the facade [`PolkurierWebservice`](../src/PolkurierWebservice.php), [`Config`](../src/Config.php) and [`ConfigProvider`](../src/ConfigProvider.php);
- the interfaces under [`Api`](../src/Api/): the services, the configuration, validation, the HTTP
  client factory and the webhook reader;
- the records under [`Entity`](../src/Entity/), which you both send and read back, with their constructors,
  their `FIELD_*` constants and their setters;
- the request and response classes under [`Dto`](../src/Dto/) and the enums under [`Enum`](../src/Enum/), including the
  envelope ([`ResponseMessage`](../src/Dto/Response/ResponseMessage.php), [`ErrorMessage`](../src/Dto/Response/ErrorMessage.php)) and [`ResponseDrift`](../src/Serializer/ResponseDrift.php);
- the builders under [`Builder`](../src/Builder/), the exceptions, [`RecordingClient`](../src/Testing/RecordingClient.php), and [`OrderReader`](../src/Webhook/OrderReader.php) with the
  [`ReadEvent`](../src/Webhook/ReadEvent.php) it answers with;
- how responses are read and payloads written: [`Api\SerializerConfigInterface`](../src/Api/SerializerConfigInterface.php) and
  [`SerializerConfig`](../src/Serializer/SerializerConfig.php), and [`Api\SerializerInterface`](../src/Api/SerializerInterface.php), which
  [`PolkurierWebservice::serializer()`](../src/PolkurierWebservice.php) returns, with the [`ReadResponse`](../src/Serializer/ReadResponse.php) it answers a body with;
- what you replace validation with: [`ValidatorRegistry`](../src/Validator/ValidatorRegistry.php), [`DefaultValidatorRegistryFactory`](../src/Validator/DefaultValidatorRegistryFactory.php),
  [`AttributeValidator`](../src/Validator/AttributeValidator.php) and [`NullValidator`](../src/Validator/NullValidator.php);
- the shape of everything above: the parameter names, because a named argument is how you call a
  constructor with many optional parameters and some frameworks pass every argument by name, and
  with them the return types, the methods themselves, the public properties and the constants and
  their values. They are recorded in `tests/Support/contract-surface.txt`, and any change to one
  fails the test suite;
- the keys of [`ValidationException::getValidationErrors()`](../src/Exception/ValidationException.php): the API's wire names, a field of a
  nested record under its parent (`sender.postcode`), a list entry with its index
  (`packs[1].weight`), and a record the payload inlines reporting at the top level;
- that a logged body hides secrets unless `logSensitiveData` is on: the API token, passwords,
  PINs, two-factor codes and tokens, and access tokens, a set that only grows. Which class does
  it, and how, is not promised.

Anything else can change in any release: the concrete factories, services and serializer setup, the
normalizers and their attributes, the validation rules and the machinery behind them, the log
preparation, and the `protected` members of every class not listed above. That includes the
envelope's own classes ([`RequestMessage`](../src/Http/Request/RequestMessage.php), [`ResponseWrapper`](../src/Http/Response/ResponseWrapper.php)), the request and response factories the facade
builds itself ([`RequestFactoryInterface`](../src/Http/Request/RequestFactoryInterface.php), [`ResponseFactoryInterface`](../src/Http/Response/ResponseFactoryInterface.php)), and the classes behind
[`Api\SerializerInterface`](../src/Api/SerializerInterface.php): [`MessageSerializer`](../src/Serializer/MessageSerializer.php), and [`SerializerProvider`](../src/Serializer/SerializerProvider.php), which
has no interface and is wired only inside the SDK.

Most classes aren't `final`, so you can subclass one to change a single step, but such a subclass
may need changes after an update. A record is the exception: subclassing it is how you add columns
of your own, and the SDK sends only the fields it declares itself.

Under [`Dto`](../src/Dto/) every class is `final` apart from [`AuthorizationMessage`](../src/Dto/Request/AuthorizationMessage.php), which a
subclass extends to carry credentials of another kind, and the abstract bases the SDK's own
messages extend. [`tests/Support/contract-surface.txt`](../tests/Support/contract-surface.txt) carries the declaration of every
promised class, `final` and `readonly` included.

Extension points
----------------

Every collaborator of [`PolkurierWebservice`](../src/PolkurierWebservice.php) you can replace is an optional constructor argument:

```php
new PolkurierWebservice(
    config: $config,
    logger: $logger,
    serializerConfig: $serializerConfig,
    configProvider: $configProvider,
    clientFactory: $clientFactory,
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
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
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

### `SerializerConfigInterface`: how responses are read

[`SerializerConfig`](../src/Serializer/SerializerConfig.php) implements it from four constructor arguments; an implementation of your
own reads the same four from wherever the application keeps them. See
[Configuration: `SerializerConfig`](configuration.md#serializerconfig). The serializer those
settings configure is handed out by [`PolkurierWebservice::serializer()`](../src/PolkurierWebservice.php) and is not replaceable
([Serialization](serialization.md#configuring-the-serializer)).

### `ValidatorInterface`: payload rules

See [Validation: changing the rules](validation.md#changing-the-rules).

### `ConfigProviderInterface`: late or per-tenant configuration

See [Configuration: per-tenant credentials](configuration.md#per-tenant-credentials).

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
            'status-' . $getStatusMessage->orderNo,
            fn() => $this->inner->getStatus($getStatusMessage),
        );
    }

    // … delegate the rest …
}
```

Or subclass the concrete service. That is shorter when you only want to change one method, but a
concrete service can change in any release (see [What a release can change](#what-a-release-can-change)):

```php
final class RetryingOrders extends OrdersService
{
    public function createOrder(CreateOrderMessage $message): CreatedOrderMessage
    {
        // … your own retry policy around parent::createOrder() …
    }
}
```

Not using the facade at all
---------------------------

[`PolkurierWebservice`](../src/PolkurierWebservice.php) is a convenience and nothing depends on it. In an application with a DI
container, it duplicates the wiring the container does. A service can be wired directly:

```php
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\OrdersService;

$configProvider = new ConfigProvider($config);
$serializer     = new MessageSerializer(new SerializerProvider());

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
