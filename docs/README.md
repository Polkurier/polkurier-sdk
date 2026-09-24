Polkurier SDK documentation
===========================

The PHP client for the Polkurier.pl web API. This directory is the long form: what the SDK is
made of, why it is put together the way it is, and how to use each part. The project
[README](../README.md) is the short version — read that first if you only need to place an order.

Start here
----------

| Page | What it covers |
|------|----------------|
| [Getting started](getting-started.md) | Installing, credentials, the first call, the shape of every call after it |
| [Configuration](configuration.md) | `Config`, `SerializerConfig`, logging, timeouts, sandbox, per-tenant credentials |
| [Services](services.md) | All sixteen services and every method on them, with what each one returns |
| [Messages and models](messages.md) | Requests, responses, enums, wire names, and why the two halves are kept apart |
| [Builders](builders.md) | Assembling a payload step by step instead of in one constructor call |
| [Validation](validation.md) | What is checked before a request is sent, how to read a rejection, how to change the rules |
| [Errors](errors.md) | Every exception the SDK raises, when, and what to do about it |
| [Serialization](serialization.md) | How PHP objects become the API's JSON and back again |
| [Architecture](architecture.md) | The request lifecycle, the collaborators, and which classes you may replace |
| [Recipes](recipes.md) | Worked solutions: placing an order, paging, labels, two-factor, payouts, retries |
| [Testing](testing.md) | Testing your integration against the SDK, and running the SDK's own suite |
| [API method index](api-methods.md) | Every `apimethod` the SDK speaks, mapped to the method that sends it |
| [More than one way](alternatives.md) | Where the SDK offers several routes to the same outcome, and which to pick |
| [Integrations](integrations/README.md) | Symfony, Laravel, Mezzio, Slim — and Magento, PrestaShop, WooCommerce, Shopware, Sylius |

The ten-second version
----------------------

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

One client, one service per functional area, one method per API endpoint. Methods return the
thing they are named for. Anything that goes wrong is an exception.

Three facts that explain most of the design
-------------------------------------------

**The API is one endpoint.** Every call is a `POST /` carrying a JSON envelope with three fields:
`apimethod`, `authorization` and `data`. There are no paths, no verbs, no status semantics. The
SDK's `ApiMethodEnum` is that first field, and everything under `src/Http` exists to build and
unwrap that envelope.

**The API answers HTTP 200 for everything**, including a bad token and a rejected payload. Never
branch on the transport status; the SDK does not, and neither should anything above it. The real
outcome is the envelope's `status` field, which the SDK reads for you and turns into either a
typed return value or an exception.

**The API's field names are not names you want in your code.** `codbankaccount`, `is_cover_address`,
`shipmenttype`, `value_nett`. Every property in the SDK is named for what it means and carries a
`#[SerializedName]` saying what the API calls it, resolved in both directions. That decoupling is a
hard requirement, not a convenience: it is what lets the wire names change without a breaking
release. See [Serialization](serialization.md).

Conventions used in these pages
-------------------------------

- Class names are given short where the namespace is obvious from context and fully qualified
  where it is not. Everything lives under `Polkurier\Webservice\`.
- "Message" means a request payload — a class under `Request` implementing `Api\MessageInterface`.
  Confusingly, many response classes are also named `…Message`; the namespace is what tells them
  apart, and [Messages and models](messages.md) explains why.
- Code that would place an order, move money or delete something is marked as such.
- Runnable versions of nearly everything here live in [`examples/`](../examples), one file per API
  method. They are safe to run: anything that writes does nothing without `--confirm`.
