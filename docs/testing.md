Testing
=======

Testing your integration
------------------------

None of this needs a network. Every collaborator is behind an interface, and the most useful one
to replace is the HTTP client.

### 1. Substitute the transport

If you replace [`ClientFactoryInterface`](../src/Api/Http/ClientFactoryInterface.php), everything above it runs as normal: request building,
serialization, envelope parsing, response mapping and exception handling. Only the network call is
fake.

The SDK includes a client for this, [`Polkurier\Webservice\Testing\RecordingClient`](../src/Testing/RecordingClient.php). It answers
every request with a canned envelope and records what it was sent:

```php
use Polkurier\Webservice\Testing\RecordingClient;

$http = new RecordingClient($body);   // the envelope to answer with (HTTP 200 unless told otherwise)
$http->willAnswerWith($otherBody);    // change the answer between calls
$http->readLastPayload();                 // the decoded body of the most recent request
$http->countRequests();                // how many requests it has seen
```

It is also its own factory, so you can pass it straight to the client:

```php
$http = new RecordingClient('{"status":"success","response":[{"servicecode":"UPS","name":"UPS Standard","foreign_shipments":false}]}');

$client = new PolkurierWebservice(config: $config, clientFactory: $http);

$carriers = $client->courier()->getAvailableCarriers();

self::assertSame('UPS', $carriers[0]->serviceCode);
self::assertSame('available_carriers', $http->readLastPayload()['apimethod']);
```

A test sees both sides: whether **the response mapped correctly**, and whether **the request
contained what was meant to be sent**. The request side catches bugs that unit tests usually miss,
e.g. a renamed field, an optional field sent as null instead of left out, or a float sent as an
int.

The API answers HTTP 200 for everything, so an error case is still a 200:

```php
$http = new RecordingClient('{"status":"error","status_code":7,"error_code":"ERROR_AUTH_DATA","response":"Błąd autoryzacji"}');

$this->expectException(AuthorizationException::class);

$client->utils()->testAuthApi($auth);
```

### 2. Substitute a service

A test of your own code that doesn't involve how the SDK talks to the API can pass in a service
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

This is faster than replacing the transport, but it tests less: it covers *your* order flow, not
whether the API is called correctly.

### 3. Mock the service interface

Code that type-hints [`Api\Service\OrdersServiceInterface`](../src/Api/Service/OrdersServiceInterface.php) instead of [`PolkurierWebservice`](../src/PolkurierWebservice.php) can be
tested with a mock of the interface, without constructing the SDK at all.

```php
final readonly class ShipmentDispatcher
{
    public function __construct(
        private OrdersServiceInterface $orders,
    ) {}
}
```

The [framework integrations](integrations/README.md) set code up this way.

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

### 5. Assert a payload, or read a recorded body

[`PolkurierWebservice::serializer()`](../src/PolkurierWebservice.php) hands out what the client sends and reads with, so a test can
write a payload or read a body without a transport at all:

```php
$serializer = $client->serializer();

self::assertJsonStringEqualsJsonString($expected, $serializer->encode($createOrderMessage));

$read = $serializer->readResponse($recordedBody, CreatedOrderMessage::class);

self::assertSame('10001234', $read->envelope->response->orderNumber);
self::assertSame([], $read->drift);
```

[`SerializerInterface::encode()`](../src/Api/SerializerInterface.php) writes the message alone, without the `apimethod` and
`authorization` around it, which [`RecordingClient::readLastPayload()`](../src/Testing/RecordingClient.php) shows instead. Asserting the
drift of a body recorded from the real API catches a response that has changed, the way
`strictResponses` does, without failing the read.

### 6. Test against the real API

None of the above catches a mapping bug, because a fixture only contains what you expected. Only a
real response shows which optional values are really optional, which nested objects can be absent
and which fields your account receives.

With a staging account, tests can run against the real API on a schedule, with
`strictResponses` turned on:

```php
$client = new PolkurierWebservice(
    config: $config,
    serializerConfig: new SerializerConfig(strictResponses: true),
);
```

A changed response then fails the build instead of being read with the change recorded.

### Summary

| Seam | Replace | Use when |
|------|---------|----------|
| Transport | [`ClientFactoryInterface`](../src/Api/Http/ClientFactoryInterface.php) | Testing that you talk to the API correctly |
| Service | `services: [Foo::class => $mock]` | Testing your flow, not the SDK |
| Interface | Inject `Api\Service\*Interface` | Most unit tests, if your design allows |
| Serializer | [`SerializerConfig`](../src/Serializer/SerializerConfig.php) | Reading strictly, so a changed response fails the test |
| Validator | [`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php) | Testing rules, or turning them off in a fixture |
| Config | [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php) | Testing per-tenant resolution |
