Getting started
===============

Requirements
------------

PHP 8.4 or 8.5. The SDK uses readonly classes, backed enums, attributes, named arguments and
`new Foo()->bar()` without parentheses throughout, and there is no polyfill path to an older
version.

Dependencies are pulled in by Composer: Guzzle for transport, `symfony/serializer` with
`property-info` and `property-access` for mapping, `psr/cache` for the optional metadata cache, and
Monolog only for its `Level` constants when logging.

Installation
------------

```bash
composer require polkurier/polkurier-sdk
```

Credentials
-----------

Two values, both from the customer panel at <https://www.polkurier.pl>:

- **login** — the numeric customer id. Not an email address.
- **token** — generated under *Settings > API token*.

They go into an `AuthorizationMessage`, which is serialized into every authenticated request. Keep
them out of version control and out of anywhere the request body gets logged verbatim; see
[Configuration](configuration.md#logging).

Your first call
---------------

```php
<?php

require 'vendor/autoload.php';

use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

$client = new PolkurierWebservice(new Config(
    authorization: new AuthorizationMessage(
        token: getenv('POLKURIER_API_TOKEN'),
        login: getenv('POLKURIER_API_LOGIN'),
    ),
    platform: 'my-shop',
    platformVersion: '1.0.0',
));

foreach ($client->courier()->availableCarriers() as $carrier) {
    printf("%s — %s\n", $carrier->getServiceCode(), $carrier->getName());
}
```

`platform` and `platformVersion` identify your integration in Polkurier's logs. Give something
stable and recognisable; they are how a support conversation about a misbehaving payload starts.

Checking the wiring before you trust it
---------------------------------------

Two methods exist for exactly this, and they are the cheapest way to tell a network problem from a
credentials problem:

```php
$client->utils()->heartbeat();        // no credentials needed — is the API reachable?
$client->utils()->testAuthApi($auth); // are these credentials good?
```

`heartbeat()` sends no authorization at all, so it answers even with a wrong token. If it succeeds
and `testAuthApi()` throws an `AuthorizationException`, the problem is the token, not the network.

The shape of every call
-----------------------

Once the client exists, every interaction follows the same three steps.

**1. Pick a service.** One accessor per functional area:

```php
$client->orders()
$client->courier()
$client->addresses()
```

The full list is in [Services](services.md). Each accessor returns an interface from
`Api\Service\` — type-hint that, not the concrete class.

**2. Build a message.** Request payloads are readonly classes taking named arguments:

```php
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

$filter = new GetOrdersMessage(
    dateFrom: new DateTimeImmutable('-30 days'),
    pageSize: 100,
    page: 1,
);
```

Methods that need no parameters take none; methods whose parameters are all optional take a
nullable message and default it to `null`. When assembling a payload across several steps is
awkward in one constructor call, the larger messages have [builders](builders.md).

**3. Call the method and use what comes back.**

```php
$list = $client->orders()->getOrders($filter);   // OrderListMessage

foreach ($list->getResult() as $order) {         // Order[]
    echo $order->getNumber();
}
```

Return types are the real thing: `Carrier[]`, an `Order`, a `bool` for endpoints that only
acknowledge. There is no envelope to unwrap and nothing to cast. If the API answers with something
other than what the method promised, you get a `MalformedResponseException` rather than a null that
explodes three lines later.

What can go wrong
-----------------

Everything the SDK raises extends `Polkurier\Webservice\Exception\Exception`, so one catch handles
the lot while subclasses let you distinguish:

```php
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

try {
    $created = $client->orders()->createOrder($order);
} catch (ValidationException $e) {
    // Nothing was sent. The payload is wrong; $e->getValidationErrors() says how.
} catch (AuthorizationException $e) {
    // The credentials are wrong or inactive. Retrying will not help.
} catch (ConnectionException $e) {
    // The request never completed — DNS, TCP, TLS, timeout. Retrying might help.
} catch (WebserviceException $e) {
    // The API refused. $e->getErrorMessage() carries the parsed error.
}
```

The order of those catches matters: `AuthorizationException` extends `WebserviceException`, and
`ValidationException` extends `InvalidArgumentException`. [Errors](errors.md) has the full
hierarchy and what each one means.

Running the examples
--------------------

`examples/` holds one runnable script per API method, named after the method it calls:

```bash
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Set `POLKURIER_API_URL` to point at something other than production. Anything that writes — placing
an order, deleting an address, requesting a payout — does nothing until you add `--confirm`, so you
can run the whole directory to see what each method answers with. Examples that need an identifier
take it as the first argument:

```bash
php examples/get_status.php POL123456789
```

Where to go next
----------------

- Placing an actual order end to end: [Recipes](recipes.md#placing-an-order)
- What every method does: [Services](services.md)
- Tuning the client for a long-running process: [Configuration](configuration.md)
- Understanding a rejection before you send it: [Validation](validation.md)
