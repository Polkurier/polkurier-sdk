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
[validation](docs/validation.md), [errors](docs/errors.md), [webhooks](docs/webhooks.md),
[serialization](docs/serialization.md) and [architecture](docs/architecture.md), plus
[worked recipes](docs/recipes.md) and
[integration guides](docs/integrations/README.md) for Symfony, Laravel, Laminas and Mezzio, PSR-11
containers, PrestaShop, WooCommerce, Shopware and Sylius. For Magento, Polkurier has its own
module, described in the [Magento page](docs/integrations/magento2.md).

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

The client has one service per functional area. Methods return typed values such as [`Carrier[]`](src/Response/Carrier.php),
an [`Order`](src/Response/Order.php), or a `bool` for calls that only acknowledge, so you don't need to unwrap or cast
anything.

| Accessor                        | Covers                                                        |
|---------------------------------|---------------------------------------------------------------|
| `$client->courier()`            | Carriers, pickups, pickup points, supported countries         |
| `$client->orders()`             | Pricing, placing, tracking, cancelling, labels, protocols     |
| `$client->addresses()`          | The account's address book                                    |
| `$client->bankAccounts()`       | Bank accounts money box payouts can be sent to                |
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

A builder class contains only its setters. The shared logic is in [`AbstractBuilder`](src/Builder/AbstractBuilder.php). Fields you
never set are not passed to the constructor, so they keep the message's own defaults.

Only messages with 4 or more fields have a builder.

Configuration
-------------

[`Config`](src/Config.php) takes the authorization message plus a platform name and version that identify your
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

The serializer has its own configuration. Pass a [`SerializerProvider`](src/Serializer/SerializerProvider.php) built with a
[`SerializerConfig`](src/Serializer/SerializerConfig.php):

| Option                | Default              | Meaning                                              |
|-----------------------|----------------------|------------------------------------------------------|
| `metadataCache`       | none                 | A PSR-6 pool to keep mapping metadata in             |
| `jsonEncodeOptions`   | readable UTF-8       | Flags for `json_encode` on the request body          |
| `timezone`            | PHP's default        | The zone a date without an offset is read in         |
| `rejectUnknownFields` | `false`              | Whether an unrecognised response field is an error   |

Reading the attributes of around 150 classes is the SDK's biggest start-up cost. The result doesn't
change between deployments of the same code, so a cache pool saves that cost in every process after
the first.

With `rejectUnknownFields` on, a response with a field the SDK doesn't read fails to map. Against a
staging API in CI, that tells you about new fields. In production, it would turn a new field into a
failed call, which is why it is off by default.

Error handling
--------------

The API returns HTTP 200 for everything, including authentication failures and validation errors,
so the HTTP status says nothing about the outcome. The SDK checks the response and throws one of
these:

| Exception                     | Raised when                                                     |
|-------------------------------|-----------------------------------------------------------------|
| `AuthorizationException`      | Credentials are missing, wrong, or the token is inactive        |
| `WebserviceException`         | Any other error reported by the API                             |
| `ConnectionException`         | The request never completed (DNS, TCP, TLS or timeout)          |
| `MalformedResponseException`  | A reply was not an API envelope, or not the shape promised      |
| `ValidationException`         | A payload was rejected before anything was sent                 |
| `InvalidArgumentException`    | A value was rejected locally, e.g. a builder missing a field    |

They all extend [`Polkurier\Webservice\Exception\Exception`](src/Exception/Exception.php). [`WebserviceException`](src/Exception/WebserviceException.php) holds the parsed
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

`status_code` and `error_code` can both be missing from an error payload, so their getters can
return null.

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

Rejecting a payload the API would accept is a bug in the SDK. `validatePayloads: false` turns off
all checks.

To drop a single rule, add your own or replace the validator, see
[Validation: changing the rules](docs/validation.md#changing-the-rules).

Architecture
------------

[`PolkurierWebservice`](src/PolkurierWebservice.php) wires up a serializer, request and response factories, an HTTP client factory
and the services. Every collaborator is an optional constructor argument typed as an interface, so
you can replace any of them: your own PSR-18 client, a different serializer setup, a configuration
provider that looks up credentials per tenant at call time, or a service of your own:

```php
new PolkurierWebservice(
    config: $config,
    services: [OrdersService::class => $myOrdersService],
);
```

Only the facade, the JSON envelope classes and a few small helpers are final. Everything else can
be subclassed: the configuration, the serializer wiring, the factories, the services, the request
and response classes, and the validation layer including the individual rules.

Requests and responses are separate classes, even where they describe the same thing, so a response
field can be non-nullable when the API always sends it. Properties are named for what they mean,
and the API's names stay in serializer attributes. A response object is either complete, or
construction fails and names the missing field.

The [architecture page](docs/architecture.md) covers the request lifecycle and every extension
point.

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

`POLKURIER_API_LOGIN` and `POLKURIER_API_TOKEN` cover the authenticated methods. Every service
method is called, and responses are read in strict mode, so a field the SDK doesn't map fails the
test. Calls that change data run only against the sandbox. Calls that can't be undone or move
money are sent with values the API rejects. Registering a two-factor token sends a code, so those
tests also need `POLKURIER_API_SEND_CODES=1`.

License
-------

MIT, see [LICENSE.md](LICENSE.md).
