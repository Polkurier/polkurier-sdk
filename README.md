Polkurier.pl API SDK
====================

PHP client for the Polkurier.pl web API: comparing carriers, pricing and placing orders, printing
labels, booking pickups and managing the account's address book, bank accounts and parcel
templates.

API documentation is available at https://www.polkurier.pl/integracje. Generate an access token in
the customer panel under Settings > API token after signing in at https://www.polkurier.pl.

Requirements
------------

PHP 8.4 or 8.5.

Installation
------------

```
composer require polkurier/polkurier-sdk
```

Getting started
---------------

```php
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

$client = new PolkurierWebservice(new Config(
    authorization: new AuthorizationMessage(token: 'your-api-token', login: '12345'),
    platform: 'my-shop',
    platformVersion: '1.0.0',
));

$carriers = $client->courier()->availableCarriers();   // Carrier[]
```

Full documentation is in [`./docs`](docs/README.md): a page each for
[configuration](docs/configuration.md), [every service](docs/services.md),
[validation](docs/validation.md), [errors](docs/errors.md), [serialization](docs/serialization.md)
and [architecture](docs/architecture.md), plus [worked recipes](docs/recipes.md) and
[integration guides](docs/integrations/README.md) for Symfony, Laravel, Magento, PrestaShop,
WooCommerce, Shopware and Sylius.

Runnable examples live in `./examples` — one per API method, named after the method it calls.
Each is standalone:

```
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Anything that writes — placing an order, deleting an address, requesting a payout — does nothing
until you add `--confirm`, so the directory is safe to run through to see what each method answers
with. Examples that need an identifier take it as the first argument.

Services
--------

The client exposes one service per functional area. Every method returns what it says it returns —
`Carrier[]`, an `Order`, a `bool` for the ones that only acknowledge — so there is nothing to
unwrap and nothing to cast.

| Accessor                        | Covers                                                        |
|---------------------------------|---------------------------------------------------------------|
| `$client->courier()`            | Carriers, pickups, pickup points, supported countries         |
| `$client->orders()`             | Pricing, placing, tracking, cancelling, labels, protocols     |
| `$client->addresses()`          | The account's address book                                    |
| `$client->bankAccounts()`       | Bank accounts used for cash-on-delivery settlement            |
| `$client->packTemplates()`      | Saved parcel templates                                        |
| `$client->geo()`                | City lookup and map tokens                                    |
| `$client->account()`            | Profile, balances, preferences, credentials, phone numbers    |
| `$client->accountVerification()`| Identity verification                                         |
| `$client->invoices()`           | Invoices and their documents                                  |
| `$client->payments()`           | Online payments and the prepaid balance                       |
| `$client->moneyBox()`           | Cash-on-delivery funds and withdrawing them                   |
| `$client->tickets()`            | Support tickets and contact forms                             |
| `$client->knowledgeBase()`      | Help articles                                                 |
| `$client->twoFactorToken()`     | Two-factor tokens for operations that need confirmation       |
| `$client->system()`             | Service limits, carrier notices, coupon checks                |
| `$client->utils()`              | Availability and credential checks                            |

Every method's docblock documents the endpoint it calls, a request payload, the success responses
including their variants, and the errors most easily hit — so the interfaces under
`src/Api/Service` double as the API reference.

Builders
--------

Messages are readonly and take named arguments, which reads well when every value is to hand:

```php
new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], searchQuery: 'Poznań', limit: 5);
```

When they are not — filters that may or may not apply, a payload assembled across a few steps —
there is a builder for each of the larger messages under `Polkurier\Webservice\Builder`:

```php
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
}

$points = $client->courier()->findCourierPoints($search->build());
```

Setters are typed exactly as the constructor is, fields left alone keep the message's own defaults,
and `build()` throws naming any required field that was never given. Nothing is validated in the
builder: the message's rules run when it is sent, so both routes reach the same checks.

Each builder is its setters and nothing else; `AbstractBuilder` holds the rest and is where to look
or to extend. A field never set is not passed to the constructor at all, so the defaults are always
the message's own.

Builders exist for messages with four or more fields. Below that a builder would be three lines to
say one. They are written by hand beside the messages, and `BuilderDriftTest` walks each one
against its message's constructor so that adding a field to one and not the other is a failing
test rather than a payload that quietly loses it.

Configuration
-------------

`Config` takes the authorization message plus a platform name and version identifying your
integration. Everything else is optional:

| Option                     | Default          | Meaning                                              |
|----------------------------|------------------|------------------------------------------------------|
| `apiUrl`                   | production       | Overrides the endpoint entirely                      |
| `sandbox`                  | `false`          | Targets the sandbox endpoint                         |
| `apiTimeout`               | `30`             | Request timeout in seconds                           |
| `loggingEnabled`           | `true`           | Enables error logging                                |
| `methodsToLog`             | `[]`             | Methods whose requests and responses are logged      |
| `validatePayloads`         | `true`           | Check payloads before sending them                   |

The SDK logs nothing unless you pass a PSR-3 logger as the second constructor argument. Note that
request bodies include the API token, so direct that logger somewhere you are willing to keep
credentials.

Serializer behaviour is configured separately, because it belongs to the application rather than
to the API. Pass a `SerializerProvider` built with your own `SerializerConfig`:

| Option                | Default              | Meaning                                              |
|-----------------------|----------------------|------------------------------------------------------|
| `metadataCache`       | none                 | A PSR-6 pool to keep mapping metadata in             |
| `jsonEncodeOptions`   | readable UTF-8       | Flags for `json_encode` on the request body          |
| `timezone`            | whatever the API set | The zone dates are read into                         |
| `rejectUnknownFields` | `false`              | Whether an unrecognised response field is an error   |

Reading the attributes off around a hundred and fifty classes is the SDK's biggest start-up cost
and never changes between deployments of the same code, so a long-running process should hand over
a cache pool. `rejectUnknownFields` is worth turning on in CI against a staging API — it tells you
the response has grown a field, instead of that field going quietly unread — and worth leaving off
in production, where a new field should not break a working integration.

Error handling
--------------

The API answers HTTP 200 for everything, including authentication failures and validation errors,
so never branch on the transport status. The SDK does that branching for you and raises:

| Exception                     | Raised when                                                     |
|-------------------------------|-----------------------------------------------------------------|
| `AuthorizationException`      | Credentials are missing, wrong, or the token is inactive        |
| `WebserviceException`         | Any other error reported by the API                             |
| `ConnectionException`         | The request never completed — DNS, TCP, TLS or timeout          |
| `MalformedResponseException`  | A reply was not an API envelope, or not the shape promised      |
| `ValidationException`         | A payload was rejected before anything was sent                 |
| `InvalidArgumentException`    | A message was constructed with values the SDK rejects locally   |

All of them extend `Polkurier\Webservice\Exception\Exception`. `WebserviceException` carries the
parsed error, the request and the response:

```php
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\WebserviceException;

try {
    $created = $client->orders()->createOrder($order);   // CreatedOrderMessage
} catch (AuthorizationException $e) {
    // Re-authenticate.
} catch (WebserviceException $e) {
    $error = $e->getErrorMessage();
    \printf("%s (%s)\n", $error->getResponse(), $error->getErrorCode() ?? 'no code');
}
```

Validation
----------

Payloads are checked before they are sent. The rules mirror what the service is documented to
require — required fields, formats, ranges — plus a few checks for payloads that are well-formed
but cannot produce the result you meant, such as a date range that runs backwards or a page size
above the documented maximum.

A rejection names the fields as they appear on the wire, in the same shape the API uses for its
own validation, so one piece of code can present both:

```php
use Polkurier\Webservice\Exception\ValidationException;

try {
    $client->orders()->createOrder($order);
} catch (ValidationException $e) {
    // Nothing was sent. ['sender.postcode' => ['must be a Polish postcode in the format NN-NNN']]
    foreach ($e->getValidationErrors() as $field => $messages) {
        \printf("%s: %s\n", $field, \implode(', ', $messages));
    }
}
```

Everything wrong with a payload is reported at once rather than one problem per attempt.

What is deliberately not checked is anything per-carrier or per-account: which services a carrier
sells, whether it needs a collection date, what an account's PIN locks are. Those depend on data
read from `available_carriers`, `get_account_info` and `get_system_info`, and the API enforces
them.

The guiding rule is that a payload the API would have accepted must never be rejected here. If the
SDK ever disagrees with the service, that is a bug worth reporting — `validatePayloads: false`
turns every check off in the meantime.

Dropping one rule rather than all of them is a subclass. Nothing in the SDK is final and its
internals are protected rather than private, so a rule you disagree with can be filtered out and
the rest kept:

```php
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\Rule\Iban;

final class WithoutIbanCheck extends AttributeValidator
{
    protected static function rulesFor(string $class): array
    {
        $rules = parent::rulesFor($class);

        foreach ($rules['values'] as $property => $validators) {
            $rules['values'][$property] = \array_values(\array_filter(
                $validators,
                static fn($rule): bool => !$rule instanceof Iban,
            ));
        }

        return $rules;
    }
}
```

Register it and pass it to the client as the `validator` argument. The same applies to the rest of
the SDK: subclass a service to change how a call is made, a factory to change what is built, the
serializer's normalizer to change what goes on the wire.

Errors arrive as exceptions rather than as return values, because a method that promises an `Order`
cannot also hand back an error and stay honest about its type. `WebserviceException::getErrorMessage()`
gives you the parsed error itself, so inspecting one is a `catch` rather than a branch. Both
`status_code` and `error_code` may be absent from an error payload, so treat them as nullable.

Architecture
------------

The source is laid out by role:

| Namespace                             | Holds                                                   |
|---------------------------------------|----------------------------------------------------------|
| `Polkurier\Webservice\Api\Service`      | One contract per service — what to type-hint            |
| `Polkurier\Webservice\Api\Validation`   | The three contracts the validation layer is built on    |
| `Polkurier\Webservice\Api`             | `ConfigInterface`, `ConfigProviderInterface`, `MessageInterface` |
| `Polkurier\Webservice\Service`         | The services that call the API                          |
| `Polkurier\Webservice\Request`        | Everything you send                                     |
| `Polkurier\Webservice\Response`       | Everything you get back                                 |
| `Polkurier\Webservice\Http`            | The transport: client, factories, and the JSON envelope |
| `Polkurier\Webservice\Serializer`      | Serializer wiring                                       |
| `Polkurier\Webservice\Validator`       | The rules, and the machinery that runs them             |
| `Polkurier\Webservice\Enum`            | Values the API accepts and returns                      |
| `Polkurier\Webservice\Exception`       | Everything the SDK throws                               |

`Config` and `ConfigProvider` sit at the root beside `PolkurierWebservice`, since they are the
wiring rather than part of any of those.

Responses are built through their constructors, so one either arrives whole or fails immediately
saying which field was missing. There is no third state where an object exists but a getter on it
raises — which matters, because a service method promising an `Order` is only as good as that.

`Api` is contracts only, and only the ones where supplying your own implementation does something:
the service interfaces, which double as the API reference, and the handful of extension points.
The three at its root are the cross-cutting ones that belong to no single area.

Requests and responses have no interfaces, deliberately. Their wire names, formats and dates live
in serializer attributes on the classes themselves, so a substitute implementation would not
serialize — an interface there advertises a swap that cannot work. Type-hint the classes.

Those attributes are also what keeps the PHP side independent of the wire: a property is named for
what it means, and `#[Serializer\SerializedName]` says what the API calls it. `codbankaccount`,
`is_cover_address` and `shipmenttype` never leak into your code.

`Request` and `Response` are split by the direction a type travels, and they sit at the root rather
than under a shared `Model`, because the direction is the distinction worth having in the namespace.
The two halves are kept apart even where they describe the same thing, because they are not the
same shape:
`Request\Order\PackMessage` carries the dimensions you choose, while the `Response\Order\Pack`
you get back also carries what the carrier charged for them, and `CodMessage` says what to collect
where `Cod` says what became of the money. Splitting them is what lets a response field be
non-nullable when the API always sends it, instead of nullable because the request had nothing to
put there.

So the pairs are `SaveMessage`/`Address`, `SaveMessage`/`PackTemplate`, `SaveMessage`/`BankAccount`,
`PackMessage`/`Pack`, `CodMessage`/`Cod`, `PickupMessage`/`Pickup`, and
`CreateOrderMessage`/`Order`. Each response class's docblock names the request that writes it.

`PolkurierWebservice` is a convenience: it wires a serializer, request and response factories, an
HTTP client factory and the services. Every collaborator is an interface, constructor-injected and
optional, so any of them can be replaced — supply your own PSR-18 client, a different serializer
setup, or a configuration provider that resolves credentials per tenant at call time.

It builds its own services by calling the constructor they happen to share. That is true of them
and not of services in general, so it does not insist on it: construct yours however it needs to be
constructed, and hand it over.

```php
new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $myOrdersService],
);
```

Anything not given is built on first use, as before.

Which classes are open to subclassing follows one rule. The infrastructure is final: the facade,
the configuration, the client, request and response factories, the JSON envelope and the
serializer wiring. Break any of those and no call works at all, and the failure looks like the API
misbehaving rather than like your own change. Each of them implements an interface instead, which
is the sanctioned way to put something else in its place.

Everything else is open, because getting it wrong costs you one call rather than all of them: the
services, every request and response class, and the whole validation layer down to the individual
rules.

Development
-----------

```
composer install
composer check      # static analysis, then tests
composer analyse    # PHPStan only
composer test       # PHPUnit only
```

The integration suite talks to a real API. `composer test` skips it; run it deliberately:

```
POLKURIER_API_URL=https://api-sandbox.polkurier.pl composer test:integration
```

That script fails rather than skips when it is not configured, because a suite that quietly passes
by doing nothing is worse than no suite. Add `POLKURIER_API_LOGIN` and `POLKURIER_API_TOKEN` to
cover the authenticated methods — and do, in CI. A fixture encodes what you expected; only a real
response tells you whether you expected right, which is where the mapping bugs are.
