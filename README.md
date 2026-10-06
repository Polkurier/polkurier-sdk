Polkurier.pl API SDK
====================

PHP client for the Polkurier.pl web API. It covers comparing carriers, pricing and placing orders,
printing labels, booking pickups, and managing the account's address book, bank accounts and parcel
templates.

The API documentation is at https://www.polkurier.pl/integracje. To get an access token, sign in at
https://www.polkurier.pl and generate one in the customer panel under Settings > API token.

Requirements
------------

PHP 8.3, 8.4 or 8.5.

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

The full documentation is in [`./docs`](docs/README.md). There are pages on
[configuration](docs/configuration.md), [every service](docs/services.md),
[validation](docs/validation.md), [errors](docs/errors.md), [serialization](docs/serialization.md)
and [architecture](docs/architecture.md), plus [worked recipes](docs/recipes.md) and
[integration guides](docs/integrations/README.md) for Symfony, Laravel, PrestaShop, WooCommerce,
Shopware and Sylius. For Magento, Polkurier has its own module, described in the
[Magento page](docs/integrations/magento2.md).

`./examples` has one runnable script per API method, named after the method it calls. Each script
runs on its own:

```
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Scripts that write anything (placing an order, deleting an address, requesting a payout) do
nothing until you add `--confirm`. You can safely run through the directory to see what each
method returns. Scripts that need an identifier take it as the first argument.

Services
--------

The client has one service per functional area. Methods return typed values such as `Carrier[]`,
an `Order`, or a `bool` for calls that only acknowledge, so you don't need to unwrap or cast
anything.

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

Each method's docblock lists the endpoint it calls, an example request payload, the possible
success responses and the most common errors. The interfaces under `src/Api/Service` are the API
reference.

Builders
--------

Messages are readonly and take named arguments. That works well when you have every value up front:

```php
new GetPointMessage(couriers: ['INPOST_PACZKOMAT'], searchQuery: 'Poznań', limit: 5);
```

If you don't (e.g. optional filters, or a payload put together over several steps), the larger
messages each have a builder under `Polkurier\Webservice\Builder`:

```php
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;

$search = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

if ($query !== null) {
    $search->searchQuery($query);
}

$points = $client->courier()->findCourierPoints($search->build());
```

Setters have the same types as the constructor parameters. `build()` throws if a required field
was never set, and the exception names the field. Builders don't validate anything themselves. The
message's rules run when it is sent, so you get the same checks either way.

A builder class contains only its setters. The shared logic is in `AbstractBuilder`. Fields you
never set are not passed to the constructor, so they keep the message's own defaults.

Only messages with 4 or more fields have a builder. The builders are written by hand next to the
messages. `BuilderDriftTest` compares each builder with its message's constructor, so a field added
to one and not the other makes the test fail.

Configuration
-------------

`Config` takes the authorization message plus a platform name and version that identify your
integration. The rest is optional:

| Option                     | Default          | Meaning                                              |
|----------------------------|------------------|------------------------------------------------------|
| `apiUrl`                   | production       | Overrides the endpoint entirely                      |
| `sandbox`                  | `false`          | Targets the sandbox endpoint                         |
| `apiTimeout`               | `30`             | Request timeout in seconds                           |
| `loggingEnabled`           | `true`           | Enables error logging                                |
| `methodsToLog`             | `[]`             | Methods whose requests and responses are logged      |
| `validatePayloads`         | `true`           | Check payloads before sending them                   |
| `logSensitiveData`         | `false`          | Log secrets such as the API token as they are        |
| `logPersonalData`          | `false`          | Log names, addresses and the like as they are        |
| `logFiles`                 | `false`          | Save files such as labels, and point to them         |
| `logFilesDirectory`        | temp directory   | Where `logFiles` saves them                          |

Nothing is logged unless you pass a PSR-3 logger as the second constructor argument. Before
anything is logged, secrets and personal data are hidden and files are left out, unless the options
above allow them. See [Configuration: logging](docs/configuration.md#logging).

The serializer has its own configuration. Pass a `SerializerProvider` built with a
`SerializerConfig`:

| Option                | Default              | Meaning                                              |
|-----------------------|----------------------|------------------------------------------------------|
| `metadataCache`       | none                 | A PSR-6 pool to keep mapping metadata in             |
| `jsonEncodeOptions`   | readable UTF-8       | Flags for `json_encode` on the request body          |
| `timezone`            | whatever the API set | The zone dates are read into                         |
| `rejectUnknownFields` | `false`              | Whether an unrecognised response field is an error   |

Reading the attributes of around 150 classes is the SDK's biggest start-up cost. The result doesn't
change between deployments of the same code, so give a long-running process a cache pool.

Turn `rejectUnknownFields` on in CI against a staging API: it tells you when a response has a new
field that the SDK doesn't read. Leave it off in production, where a new field shouldn't break a
working integration.

Error handling
--------------

The API returns HTTP 200 for everything, including authentication failures and validation errors,
so don't branch on the HTTP status. The SDK checks the response for you and throws one of these:

| Exception                     | Raised when                                                     |
|-------------------------------|-----------------------------------------------------------------|
| `AuthorizationException`      | Credentials are missing, wrong, or the token is inactive        |
| `WebserviceException`         | Any other error reported by the API                             |
| `ConnectionException`         | The request never completed (DNS, TCP, TLS or timeout)          |
| `MalformedResponseException`  | A reply was not an API envelope, or not the shape promised      |
| `ValidationException`         | A payload was rejected before anything was sent                 |
| `InvalidArgumentException`    | A message was constructed with values the SDK rejects locally   |

They all extend `Polkurier\Webservice\Exception\Exception`. `WebserviceException` holds the parsed
error, the request and the response:

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

Payloads are checked before they are sent. The rules follow what the API documentation requires
(required fields, formats, ranges). There are also a few checks for payloads that are well-formed
but can't do what you meant, such as a date range that runs backwards or a page size above the
documented maximum.

Errors are keyed by the field names used on the wire, in the same shape the API uses for its own
validation errors. The same code can display both:

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

All problems with a payload are reported together.

The SDK doesn't check anything that depends on the carrier or the account: which services a carrier
offers, whether it needs a collection date, or what PIN locks an account has. That data comes from
`available_carriers`, `get_account_info` and `get_system_info`, and the API checks these rules
itself.

The SDK should never reject a payload the API would accept. If it does, please report it as a bug.
Until it's fixed, `validatePayloads: false` turns off all checks.

To drop a single rule, subclass the validator. The validation layer is not final and its internals
are protected, so you can filter out one rule and keep the others:

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

Register it and pass it to the client as the `validator` argument. Services and factories work the
same way: subclass a service to change how a call is made, or a factory to change what it builds.
The configuration and serializer classes can be subclassed too, or replaced through their
interfaces (see [Architecture](#architecture)).

Errors are thrown as exceptions. A method that returns an `Order` can't also return an error.
`WebserviceException::getErrorMessage()` returns the parsed error, so you handle it in a `catch`
block. `status_code` and `error_code` can both be missing from an error payload, so treat them as
nullable.

Architecture
------------

The source is organised by role:

| Namespace                             | Holds                                                   |
|---------------------------------------|----------------------------------------------------------|
| `Polkurier\Webservice\Api\Service`      | One contract per service, which is what to type-hint    |
| `Polkurier\Webservice\Api\Validation`   | The three contracts the validation layer is built on    |
| `Polkurier\Webservice\Api`             | `ConfigInterface`, `ConfigProviderInterface`, `MessageInterface` |
| `Polkurier\Webservice\Service`         | The services that call the API                          |
| `Polkurier\Webservice\Request`        | Everything you send                                     |
| `Polkurier\Webservice\Response`       | Everything you get back                                 |
| `Polkurier\Webservice\Http`            | The transport: client, factories, and the JSON envelope |
| `Polkurier\Webservice\Serializer`      | Serializer wiring                                       |
| `Polkurier\Webservice\Validator`       | The rules, and the code that runs them                  |
| `Polkurier\Webservice\Enum`            | Values the API accepts and returns                      |
| `Polkurier\Webservice\Exception`       | Everything the SDK throws                               |

`Config` and `ConfigProvider` are in the root namespace next to `PolkurierWebservice`, because they
belong to the wiring and not to any of the areas above.

Responses are built through their constructors. A response object is either complete, or
construction fails and names the missing field. You never get an object whose getters throw.

`Api` contains only contracts you can usefully implement yourself: the service interfaces (which
are also the API reference) and a few extension points. The 3 in the root of `Api` are used across
several areas.

Requests and responses have no interfaces. Their wire names, formats and dates are defined in
serializer attributes on the classes, so a different implementation would not serialize. Type-hint
the classes.

The same attributes keep PHP names separate from wire names. A property is named for what it
means, and `#[Serializer\SerializedName]` gives the API's name for it. You won't see
`codbankaccount`, `is_cover_address` or `shipmenttype` in your code.

`Request` and `Response` are split by the direction data travels, and both sit at the root instead
of under a shared `Model` namespace. They stay separate even when they describe the same thing,
because the shapes differ. `Request\Order\PackMessage` holds the dimensions you choose. The
`Response\Order\Pack` you get back also holds what the carrier charged for them. `CodMessage` says
what to collect, and `Cod` says what happened to the money. Keeping them apart means a response
field can be non-nullable when the API always sends it.

The pairs are `SaveMessage`/`Address`, `SaveMessage`/`PackTemplate`, `SaveMessage`/`BankAccount`,
`PackMessage`/`Pack`, `CodMessage`/`Cod`, `PickupMessage`/`Pickup`, and
`CreateOrderMessage`/`Order`. Each response class's docblock names the request that writes it.

`PolkurierWebservice` wires up a serializer, request and response factories, an HTTP client factory
and the services. Every collaborator is an optional constructor argument typed as an interface, so
you can replace any of them. For example, you can pass your own PSR-18 client, a different
serializer setup, or a configuration provider that looks up credentials per tenant at call time.

The built-in services all share one constructor signature, and `PolkurierWebservice` uses it to
create them. Your own services don't need that signature. Construct them however you like and pass
them in:

```php
new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $myOrdersService],
);
```

Services you don't pass are created on first use.

Only the facade, the JSON envelope classes and a few small helpers are final. Everything else can
be subclassed: the configuration, the serializer wiring, the client, request and response
factories, the services, all request and response classes, and the validation layer including the
individual rules. Most of them also implement an interface, so you can replace them outright.

Development
-----------

```
composer install
composer check      # static analysis, then tests
composer analyse    # PHPStan only
composer test       # PHPUnit only
```

The integration suite talks to a real API. `composer test` skips it, so run it separately:

```
POLKURIER_API_URL=https://api-sandbox.polkurier.pl composer test:integration
```

That script fails instead of skipping when it isn't configured, so it can't pass without running
anything. Set `POLKURIER_API_LOGIN` and `POLKURIER_API_TOKEN` as well to cover the authenticated
methods. Do this in CI. Fixtures only encode what you expected the API to return. Mapping bugs show
up against real responses.
