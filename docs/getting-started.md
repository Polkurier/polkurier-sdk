Getting started
===============

Requirements
------------

PHP 8.3, 8.4 or 8.5. The SDK uses readonly classes, backed enums, attributes, named arguments and
typed class constants, and there is no polyfill for older versions.

Composer installs the dependencies: Guzzle for HTTP, `symfony/serializer` with `property-info` and
`property-access` for mapping (Symfony 6.4, 7.4 or 8.x), `psr/cache` for the optional metadata cache, and the PSR interfaces
for HTTP clients, HTTP messages and logging. Any PSR-3 logger works.

Installation
------------

```bash
composer require polkurier/polkurier-sdk
```

Credentials
-----------

You need two values from the customer panel at <https://www.polkurier.pl>:

- `login`: your numeric customer id (not your email address).
- `token`: generate one under *Settings > API token*.

Both go into an [`AuthorizationMessage`](../src/Http/Request/AuthorizationMessage.php), which is included in every authenticated request. The SDK
hides the token in its logs unless you allow it, see [Configuration](configuration.md#logging).

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

`platform` and `platformVersion` identify your integration in the `User-Agent` header of every
request.

Checking the connection
-----------------------

These two methods tell you whether a problem is the network or the credentials:

```php
$client->utils()->heartbeat();        // no credentials needed: is the API reachable?
$client->utils()->testAuthApi($auth); // are these credentials good?
```

[`UtilsServiceInterface::heartbeat()`](../src/Api/Service/UtilsServiceInterface.php) sends no authorization, so it works even with a wrong token. If it succeeds and
[`UtilsServiceInterface::testAuthApi()`](../src/Api/Service/UtilsServiceInterface.php) throws an [`AuthorizationException`](../src/Exception/AuthorizationException.php), the token is the problem.

Making a call
-------------

**1. Pick a service.** There is one accessor per functional area:

```php
$client->orders()
$client->courier()
$client->addresses()
```

The full list is in [Services](services.md). Each accessor returns an interface from
`Api\Service\`.

**2. Build a message.** Request payloads are readonly classes that take named arguments:

```php
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

$filter = new GetOrdersMessage(
    dateFrom: new DateTimeImmutable('-30 days'),
    pageSize: 100,
    page: 1,
);
```

Methods with no parameters take no message, and an optional message defaults to `null`. If you
need to build a payload over several steps, the larger messages have [builders](builders.md).

**3. Call the method and use the result.**

```php
$list = $client->orders()->getOrders($filter);   // OrderListMessage

foreach ($list->getResult() as $order) {         // Order[]
    echo $order->getNumber();
}
```

Methods return typed values such as `Carrier[]`, an [`Order`](../src/Response/Order.php), or a `bool` for endpoints that only
acknowledge. You don't need to unwrap or cast anything. If the API returns something that doesn't
match the method's return type, you get a [`MalformedResponseException`](../src/Exception/MalformedResponseException.php) instead of an unexpected
null.

What can go wrong
-----------------

Every exception the SDK throws extends [`Polkurier\Webservice\Exception\Exception`](../src/Exception/Exception.php), so you can catch
them all at once, or catch the subclasses separately:

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
    // The request never completed (DNS, TCP, TLS, timeout). Retrying might help.
} catch (WebserviceException $e) {
    // The API refused. $e->getErrorMessage() carries the parsed error.
}
```

The order of the catch blocks matters. [`AuthorizationException`](../src/Exception/AuthorizationException.php) extends [`WebserviceException`](../src/Exception/WebserviceException.php),
and [`ValidationException`](../src/Exception/ValidationException.php) extends [`InvalidArgumentException`](../src/Exception/InvalidArgumentException.php). See [Errors](errors.md) for the full
hierarchy.

Running the examples
--------------------

`examples/` has one runnable script per API method, named after the method it calls:

```bash
POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
```

Set `POLKURIER_API_URL` to use an endpoint other than production, or `POLKURIER_API_SANDBOX=1` to
use the sandbox. Scripts that write anything
(placing an order, deleting an address, requesting a payout) do nothing until you add `--confirm`,
so you can run all of them to see what each method returns. Scripts that need an identifier take it
as the first argument:

```bash
php examples/get_status.php 12345-678
```
