Testing
=======

This page covers testing your own integration against the SDK, and then running the SDK's own
test suite.

Testing your integration
------------------------

None of this needs a network. Every collaborator is behind an interface, and the most useful one
to replace is the HTTP client.

### 1. Substitute the transport (recommended)

If you replace `ClientFactoryInterface`, everything above it runs as normal: request building,
serialization, envelope parsing, response mapping and exception handling. Only the network call is
fake.

The SDK includes a client for this, `Polkurier\Webservice\Testing\RecordingClient`. It answers
every request with a canned envelope and records what it was sent:

```php
use Polkurier\Webservice\Testing\RecordingClient;

$http = new RecordingClient($body);   // the envelope to answer with (HTTP 200 unless told otherwise)
$http->willAnswerWith($otherBody);    // change the answer between calls
$http->lastPayload();                 // the decoded body of the most recent request
$http->requestCount();                // how many requests it has seen
```

It is also its own factory, so you can pass it straight to the client:

```php
$http = new RecordingClient('{"status":"success","response":[{"servicecode":"UPS","name":"UPS Standard","foreign_shipments":false}]}');

$client = new PolkurierWebservice(config: $config, clientFactory: $http);

$carriers = $client->courier()->availableCarriers();

self::assertSame('UPS', $carriers[0]->getServiceCode());
self::assertSame('available_carriers', $http->lastPayload()['apimethod']);
```

In each test, check that **the response mapped correctly** and that **the request contained what you
meant to send**. The second check catches bugs that unit tests usually miss, e.g. a renamed field,
an optional field sent as null instead of left out, or a float sent as an int.

The API answers HTTP 200 for everything, so an error case is still a 200:

```php
$http = new RecordingClient('{"status":"error","response":"Invalid auth data","status_code":401,"error_code":"INVALID_AUTH_DATA"}');

$this->expectException(AuthorizationException::class);

$client->utils()->testAuthApi($auth);
```

### 2. Substitute a service

If you are testing your own code and do not care how the SDK talks to the API, pass in a service
instead. Nothing is built or serialized:

```php
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Service\OrdersService;

$orders = $this->createMock(OrdersServiceInterface::class);
$orders->method('createOrder')->willReturn($cannedCreatedOrder);

$client = new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $orders],
);
```

The key is the concrete class the accessor would have built. The value only has to implement the
interface that the accessor returns.

This is faster than replacing the transport, but it tests less. Use it to test *your* order flow.
Replace the transport to test that you are calling the API correctly.

### 3. Don't use the facade at all

Your code should type-hint `Api\Service\OrdersServiceInterface` instead of `PolkurierWebservice`.
Then most of your tests need neither of the approaches above. Pass a mock of the interface directly
and do not construct the SDK at all.

```php
final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}
}
```

This is how the SDK is meant to be used, and it is what the
[framework integrations](integrations/README.md) set up.

### 4. Test the validator on its own

A validator is a plain object with one public method, so you can check a payload without a client:

```php
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Validator\AttributeValidator;

try {
    (new AttributeValidator())->validate($message);
    self::fail('Expected a rejection.');
} catch (ValidationException $e) {
    self::assertArrayHasKey('sender.postcode', $e->getValidationErrors());
}
```

Do this for every payload your code builds. These tests are fast, and they catch mistakes that
would otherwise each cost a round trip to the API.

### 5. Test against the real API

None of the above catches a mapping bug, because a fixture only contains what you expected. Only a
real response shows which optional values are really optional, which nested objects can be absent
and which fields your account receives.

If your integration has a staging account, run tests against it on a schedule, with
`rejectUnknownFields` turned on:

```php
new SerializerProvider(new SerializerConfig(rejectUnknownFields: true));
```

A new field in a response then fails the build instead of being silently ignored.

### Summary

| Seam | Replace | Use when |
|------|---------|----------|
| Transport | `ClientFactoryInterface` | Testing that you talk to the API correctly |
| Service | `services: [Foo::class => $mock]` | Testing your flow, not the SDK |
| Interface | Inject `Api\Service\*Interface` | Most unit tests, if your design allows |
| Serializer | `SerializerProviderInterface` | Testing mapping changes of your own |
| Validator | `ValidatorInterface` | Testing rules, or turning them off in a fixture |
| Config | `ConfigProviderInterface` | Testing per-tenant resolution |

---

The SDK's own suite
-------------------

```bash
composer install
composer check      # static analysis, then tests
composer analyse    # PHPStan only
composer test       # PHPUnit only
```

PHPStan runs at level 6 over `src`, `tests` **and** `examples`. Type-checking the examples keeps
them in step with the code they demonstrate.

### What is covered

| Test | Guards |
|------|--------|
| `Serializer/ResponseConstructionTest` | Every response class builds in full or refuses, naming what it could not fill |
| `Serializer/AbsentValueTest` | The 3 empty forms: `[]` for an object, `""` for a date, absent keys |
| `Serializer/SerializerConfigTest` | The JSON flags actually reach the encoder |
| `SerializationTest` | Round-tripping, wire names, omitted-when-empty behaviour |
| `Http/ResponseFactoryTest` | Envelope parsing, the 5 response formats, malformed bodies |
| `Message/RequestMessageConstructionTest` | Requests can be constructed as documented |
| `Validator/RuleTest` | Each rule in isolation |
| `Validator/ValidationTest` | Whole payloads, and the pooled reports |
| `Validator/ValidatorWiringTest` | Every message's declared validators exist and are reachable |
| `Builder/BuilderBehaviourTest` | Setters, defaults, missing-field reporting |
| `Builder/BuilderDriftTest` | Every builder still matches its message's constructor |
| `Webservice/FacadeTest` | Lazy construction and reuse of services |
| `Webservice/ServiceCallTest` | End-to-end through the fake transport |
| `Webservice/ServiceSubstitutionTest` | Handing in your own service works |
| `Entity/AddressTest` | The one entity with enough behaviour to test |
| `ApiCodeEnumTest` | Status and error code enums stay in step |

`BuilderDriftTest` compares each builder with its message's constructor. It checks that every
constructor parameter has a setter with a matching type, that no setter is left over after its
parameter is removed, and that every message with 4 or more fields has a builder. If you add a
field and forget the builder, this test fails.

### The integration suite

```bash
POLKURIER_API_URL=https://api-sandbox.polkurier.pl composer test:integration
```

`composer test` skips it. `composer test:integration` **fails** when it is not configured, so it
cannot pass without running anything.

Set `POLKURIER_API_LOGIN` and `POLKURIER_API_TOKEN` to cover the authenticated methods. Do this in
CI. Without them only the unauthenticated methods run.

`tests/Integration/LiveApiTest.php` runs against real responses and catches mapping bugs that
fixtures miss. It is read-only: nothing in it places an order, moves money or deletes anything.

### Adding a test

Unit tests go under `tests/Unit`, mirroring the namespace they cover, with `#[CoversClass]`.
Anything touching the network goes under `tests/Integration` with `#[Group('integration')]` and
`#[CoversNothing]`.

PHPUnit is configured to fail on warnings, notices and deprecations. A test that emits a
deprecation fails.

Run `composer check` instead of `composer test`, so static analysis runs too. It catches problems
that a passing test suite does not show.
