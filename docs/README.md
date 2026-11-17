Polkurier SDK documentation
===========================

Start here
----------

| Page | What it covers |
|------|----------------|
| [Getting started](getting-started.md) | Installation, credentials, the first call, and how every call works |
| [Configuration](configuration.md) | [`Config`](../src/Config.php), [`SerializerConfig`](../src/Serializer/SerializerConfig.php), logging, timeouts, sandbox, per-tenant credentials |
| [Services](services.md) | Everything the API offers, grouped by task: every method and what it does |
| [Messages and models](messages.md) | Requests, responses, the one class each stored record has, enums, wire names |
| [Builders](builders.md) | Building a payload step by step instead of in one constructor call |
| [Validation](validation.md) | What is checked before a request is sent, reading a rejection, changing the rules |
| [Errors](errors.md) | Every exception the SDK throws, when, and how to handle it |
| [Serialization](serialization.md) | How PHP objects are converted to the API's JSON and back, and writing or reading a body yourself |
| [Architecture](architecture.md) | The request lifecycle, the collaborators, and which classes you can replace |
| [Webhooks](webhooks.md) | Checking and reading the order events Polkurier posts to your system |
| [Recipes](recipes.md) | Worked examples: placing an order, paging, labels, two-factor, payouts, retries |
| [Testing](testing.md) | Testing your integration against the SDK |
| [API method index](api-methods.md) | Every `apimethod` the SDK supports and the method that sends it |
| [Postman collection](postman/README.md) | Every API method as raw requests with example responses, to try the API from Postman |
| [More than one way](alternatives.md) | Where the SDK offers several ways to do the same thing, and which to use |
| [Integrations](integrations/README.md) | Symfony, Laravel, Laminas and Mezzio, Slim and any PSR-11 container, PrestaShop, WooCommerce, Shopware, Sylius, and Polkurier's Magento module |

Quick example
-------------

```php
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

$client = new PolkurierWebservice(new Config(
    authorization: new AuthorizationMessage(token: 'your-api-token', login: '12345'),
    platform: 'my-shop',
    platformVersion: '1.0.0',
));

$carriers = $client->courier()->availableCarriers();   // Carrier[]
```

The client has one service per functional area and a method for each API endpoint. Methods
return typed results and throw exceptions for errors.

How the API works
-----------------

**The API has a single endpoint.** Every call is a `POST /` with a JSON envelope containing
`apimethod`, plus `authorization` and `data` when the call has them. There are no paths, HTTP
verbs or status codes to deal with. [`ApiMethodEnum`](../src/Enum/Http/ApiMethodEnum.php) holds the values for `apimethod`, and the code under `src/Http` builds and
unwraps the envelope.

**The API returns HTTP 200 for everything**, including a bad token and a rejected payload. The SDK
doesn't look at the HTTP status. It reads the envelope's `status` field and either returns a typed
value or throws an exception.

**The API's field names are awkward to use in PHP**, e.g. `codbankaccount`, `is_cover_address`,
`shipmenttype`, `value_nett`. SDK properties are named for what they mean, and a
`#[SerializedName]` attribute maps each one to the API's name in both directions. This mapping is
required: it means the wire names can change without a breaking release. See
[Serialization](serialization.md).

Messages and examples
---------------------

- "Message" means a payload you send: a class under `Dto\Request`, or an entity class whose name
  ends in `Message`, implementing [`Api\MessageInterface`](../src/Api/MessageInterface.php). Many classes you read are also named
  `…Message`; the namespace tells them apart. [Messages and models](messages.md) explains why.
- [`examples/`](../examples) has runnable versions of most of this, one file per API method. They
  are safe to run, because scripts that write do nothing without `--confirm`.
