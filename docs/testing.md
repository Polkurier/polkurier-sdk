Testing
=======

Two different jobs: testing an integration you are writing against the SDK, and running the SDK's
own suite. This page covers both, starting with yours.

Testing your integration
------------------------

The SDK is designed so that none of this needs a network. Every collaborator is behind an
interface, and the useful seam is the HTTP client.

### 1. Substitute the transport (recommended)

Replace `ClientFactoryInterface` and everything above it runs for real — request building,
serialization, envelope parsing, response mapping, exception classification. Only the socket is
fake.

The SDK ships a client for exactly this, `Polkurier\Webservice\Testing\RecordingClient`. It
answers every request with a canned envelope and remembers what it was sent:

```php
use Polkurier\Webservice\Testing\RecordingClient;

$http = new RecordingClient($body);   // the envelope to answer with; HTTP 200 unless told otherwise
$http->willAnswerWith($otherBody);    // change the answer between calls
$http->lastPayload();                 // the decoded body of the most recent request
$http->requestCount();                // how many requests it has seen
```

It doubles as its own factory, which is what lets it be handed straight to the client:

```php
$http = new RecordingClient('{"status":"success","response":[{"servicecode":"UPS","name":"UPS Standard","foreign_shipments":false}]}');

$client = new PolkurierWebservice(config: $config, clientFactory: $http);

$carriers = $client->courier()->availableCarriers();

self::assertSame('UPS', $carriers[0]->getServiceCode());
self::assertSame('available_carriers', $http->lastPayload()['apimethod']);
```

Two assertions worth making in every such test: **what came back mapped correctly**, and **what went
out was what you meant**. The second catches the class of bug that unit tests otherwise miss
entirely — a field renamed, an optional field serialized as null instead of being omitted, a float
sent as an int.

Remember the API answers HTTP 200 for everything, so an error case is still a 200:

```php
$http = new RecordingClient('{"status":"error","response":"Invalid auth data","status_code":401,"error_code":"INVALID_AUTH_DATA"}');

$this->expectException(AuthorizationException::class);

$client->utils()->testAuthApi($auth);
```

### 2. Substitute a service

When you are testing your own code and do not care how the SDK talks to anything, hand in a
service instead. Nothing gets built, nothing gets serialized:

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

The key is the concrete class the accessor would have built; the value only has to satisfy the
interface that accessor returns.

This is faster and blinder than the transport seam. Use it for testing *your* order flow; use the
transport seam for testing that you are talking to the API correctly.

### 3. Don't use the facade at all

Your code should be type-hinting `Api\Service\OrdersServiceInterface`, not `PolkurierWebservice`.
If it is, most of your tests need neither of the above — pass a mock of that interface directly and
never construct the SDK.

```php
final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}
}
```

This is the shape the SDK is built for, and it is what the
[framework integrations](integrations/README.md) wire up.

### 4. Test the validator on its own

A validator is a plain object with one public method, so a payload can be checked without any
client at all:

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

Worth doing for every payload your code builds. It is the cheapest test in the suite and it
catches the mistakes that would otherwise cost a round trip each.

### 5. Test against a real API, deliberately

Nothing above catches a mapping bug, because a fixture you wrote is a fixture you already
understood. A fixture encodes what you expected; only a real response tells you whether you
expected right — which optional values are genuinely optional, which nested objects can be absent,
which fields your account actually receives.

So if your integration has a staging account, run something against it on a schedule — and turn
`rejectUnknownFields` on when you do:

```php
new SerializerProvider(new SerializerConfig(rejectUnknownFields: true));
```

That turns "the API grew a field" from a silent omission into a failing build.

### What to fake, in one table

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

PHPStan runs at level 6 over `src`, `tests` **and** `examples` — the examples are type-checked, not
just written, which is the only thing that stops them drifting away from the API they demonstrate.

### What is covered

| Test | Guards |
|------|--------|
| `Serializer/ResponseConstructionTest` | Every response class builds wholly or refuses, naming what it could not fill |
| `Serializer/AbsentValueTest` | The three empties: `[]` for an object, `""` for a date, absent keys |
| `Serializer/SerializerConfigTest` | The JSON flags actually reach the encoder |
| `SerializationTest` | Round-tripping, wire names, omitted-when-empty behaviour |
| `Http/ResponseFactoryTest` | Envelope parsing, the five response formats, malformed bodies |
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

`BuilderDriftTest` is the interesting one. It walks each builder against its message's constructor
and asserts three things: every constructor parameter has a setter with a matching type, no setter
outlives its parameter, and no message with four or more fields lacks a builder. Adding a field and
forgetting its builder is a failing test rather than a payload that quietly loses it.

### The integration suite

```bash
POLKURIER_API_URL=https://api-sandbox.polkurier.pl composer test:integration
```

`composer test` skips it. `composer test:integration` **fails** rather than skips when it is not
configured, because a suite that quietly passes by doing nothing is worse than no suite.

Add `POLKURIER_API_LOGIN` and `POLKURIER_API_TOKEN` to cover the authenticated methods — and do, in
CI. Without them only the unauthenticated methods run.

`tests/Integration/LiveApiTest.php` is where the real-response bugs listed above were found. It is
read-only: nothing in it places an order, moves money or deletes anything.

### Adding a test

Unit tests go under `tests/Unit`, mirroring the namespace they cover, with `#[CoversClass]`.
Anything touching the network goes under `tests/Integration` with `#[Group('integration')]` and
`#[CoversNothing]`.

PHPUnit is configured to fail on warnings, notices and deprecations, so a test that passes while
emitting a deprecation is not passing.

Run `composer check`, not `composer test`. Static analysis catches what a green suite does not — an
assertion that asserts nothing type-checks perfectly well and tells you nothing.
