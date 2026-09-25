Polkurier SDK documentation
===========================

These pages document the PHP client for the Polkurier.pl web API in detail: what the SDK is made
of, how it fits together, and how to use each part. The project [README](../README.md) is the short
version. If you only need to place an order, start there.

Start here
----------

| Page | What it covers |
|------|----------------|
| [Getting started](getting-started.md) | Installation, credentials, the first call, and how every call works |
| [Configuration](configuration.md) | `Config`, `SerializerConfig`, logging, timeouts, sandbox, per-tenant credentials |
| [Services](services.md) | All 16 services and their methods, with return types |
| [Messages and models](messages.md) | Requests, responses, enums, wire names, and why requests and responses are separate classes |
| [Builders](builders.md) | Building a payload step by step instead of in one constructor call |
| [Validation](validation.md) | What is checked before a request is sent, reading a rejection, changing the rules |
| [Errors](errors.md) | Every exception the SDK throws, when, and how to handle it |
| [Serialization](serialization.md) | How PHP objects are converted to the API's JSON and back |
| [Architecture](architecture.md) | The request lifecycle, the collaborators, and which classes you can replace |
| [Recipes](recipes.md) | Worked examples: placing an order, paging, labels, two-factor, payouts, retries |
| [Testing](testing.md) | Testing your integration against the SDK, and running the SDK's own tests |
| [API method index](api-methods.md) | Every `apimethod` the SDK supports and the method that sends it |
| [More than one way](alternatives.md) | Where the SDK offers several ways to do the same thing, and which to use |
| [Integrations](integrations/README.md) | Symfony, Laravel, Mezzio, Slim, Magento, PrestaShop, WooCommerce, Shopware, Sylius |

Quick example
-------------

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

The client has one service per functional area and one method per API endpoint. Each method
returns the type it is named for. Errors are thrown as exceptions.

How the API works
-----------------

**The API has a single endpoint.** Every call is a `POST /` with a JSON envelope containing
`apimethod`, `authorization` and `data`. There are no paths, HTTP verbs or status codes to deal
with. `ApiMethodEnum` holds the values for `apimethod`, and the code under `src/Http` builds and
unwraps the envelope.

**The API returns HTTP 200 for everything**, including a bad token and a rejected payload. Don't
check the HTTP status. The SDK doesn't either. It reads the envelope's `status` field and either
returns a typed value or throws an exception.

**The API's field names are awkward to use in PHP**, e.g. `codbankaccount`, `is_cover_address`,
`shipmenttype`, `value_nett`. SDK properties are named for what they mean, and a
`#[SerializedName]` attribute maps each one to the API's name in both directions. This mapping is
required: it means the wire names can change without a breaking release. See
[Serialization](serialization.md).

Conventions used in these pages
-------------------------------

- Class names are written short when the namespace is clear from context, and fully qualified when
  it isn't. Everything is under `Polkurier\Webservice\`.
- "Message" means a request payload: a class under `Request` that implements
  `Api\MessageInterface`. Many response classes are also named `…Message`, which can be confusing.
  The namespace tells them apart. [Messages and models](messages.md) explains why.
- Code that places an order, moves money or deletes something is marked.
- [`examples/`](../examples) has runnable versions of most of this, one file per API method. They
  are safe to run, because scripts that write do nothing without `--confirm`.
