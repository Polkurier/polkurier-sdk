Configuration
=============

There are two configuration objects, and they are separate on purpose.

`Config` is about the API: where it is, who you are, how long to wait, what to log.
`SerializerConfig` is about your application: how it caches, what timezone it thinks in, how strict
it wants to be about fields it does not recognise. Nothing in the second one depends on the first,
and an application that runs two clients against two accounts will usually share one serializer
between them.

There are three ways to give the SDK its configuration, and they are not alternatives to each
other so much as three levels of lateness:

1. **A `Config` object**, built once and passed to the client. The ordinary case, covered
   immediately below.
2. **Your own `ConfigInterface` implementation**, when the values come from somewhere that is not
   a constructor call — a settings table, a framework's parameter bag, a feature flag. `Config` is
   `final`, so this means implementing the interface rather than extending the class.
3. **Your own `ConfigProviderInterface`**, when the configuration is not known until the moment it
   is used — per tenant, per shop, per job. See [per-tenant credentials](#per-tenant-credentials).

They compose: a provider hands back a `ConfigInterface`, which may well be a plain `Config`.

`Config`
--------

```php
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

$config = new Config(
    authorization: new AuthorizationMessage(token: '…', login: '12345'),
    platform: 'my-shop',
    platformVersion: '1.0.0',
);
```

Three arguments are required; the rest have working defaults.

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `authorization` | `AuthorizationMessage` | — | Customer id and API token |
| `platform` | `string` | — | Name identifying your integration |
| `platformVersion` | `string` | — | Its version |
| `apiUrl` | `?string` | `null` | Overrides the endpoint entirely |
| `apiTimeout` | `int` | `30` | Request timeout, in seconds |
| `sandbox` | `bool` | `false` | Targets the sandbox host |
| `loggingEnabled` | `bool` | `true` | Enables error logging |
| `methodsToLog` | `ApiMethodEnum[]` | `[]` | Methods whose full traffic is logged |
| `validatePayloads` | `bool` | `true` | Check payloads before sending them |

`Config` is `final readonly` and implements `Api\ConfigInterface`. To vary configuration at
runtime, implement the interface or supply a different provider rather than mutating this.

### Choosing an endpoint

An explicit `apiUrl` wins. Failing that, `sandbox` chooses between the two constants on `Config`:

```php
Config::API_URL         // https://api.polkurier.pl
Config::SANDBOX_API_URL // https://api-sandbox.polkurier.pl
```

So `sandbox: true` is the ordinary way to develop, and `apiUrl` is for a local mirror or a staging
host that is neither.

```php
new Config(
    authorization: $auth,
    platform: 'my-shop',
    platformVersion: '1.0.0',
    apiUrl: getenv('POLKURIER_API_URL') ?: null,
    sandbox: (bool)getenv('POLKURIER_SANDBOX'),
);
```

Note that `sandbox` is also reported in every log record the SDK writes, so a misdirected request
is visible in the log rather than only in a packet capture.

### Timeouts

`apiTimeout` is passed straight to Guzzle as its `timeout` option and covers the whole request.
Thirty seconds is generous for most calls and not generous for a few: `get_label` with a dozen
order numbers renders a dozen labels into one PDF, and `get_orders` with `packs`, `items` and
`files` all requested is a large response. If you are batching, raise it.

A request that runs out of time surfaces as a `ConnectionException`, which is the same class you
get for DNS and TLS failures — the SDK cannot tell you whether the order was placed before the
timeout, only that the answer never arrived. See
[Recipes: retrying safely](recipes.md#retrying-safely).

### Logging

The SDK logs nothing unless you hand it a PSR-3 logger:

```php
$client = new PolkurierWebservice($config, $logger);
```

There are then two levels, and they are controlled separately.

**Error logging** follows `loggingEnabled`. It covers API errors and transport failures: the
request that failed, its body, and whatever came back. This is on by default, which costs nothing
while no logger is attached.

**Extended logging** covers successful traffic too — the full request and response of every call —
and is opt-in per method:

```php
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;

new Config(
    // …
    methodsToLog: [ApiMethodEnum::CreateOrder, ApiMethodEnum::CancelOrder],
);
```

Both flags are read through `isExtendedLoggingEnabled()`, which requires `loggingEnabled` as well —
turning base logging off silences everything, whatever `methodsToLog` says.

> **Request bodies contain the API token.** `AuthorizationMessage` is serialized into every
> authenticated request, and the logger is handed the body verbatim. Direct that logger somewhere
> you are willing to keep credentials, or filter the record before it lands. Nothing in the SDK
> redacts it, because a redacting logger that silently also redacted something you needed would be
> worse than an obvious rule.

Each record also carries structured context — `method`, `sandbox`, `payload`, `request`,
`parsed_response`, `response` — so a processor can pick out what it wants rather than parsing the
message string.

### Payload validation

`validatePayloads` decides whether the SDK checks a message before spending a request on it. On by
default. Turning it off replaces the validator with one that accepts everything, so the code path
is the same either way.

The one reason to turn it off is that the SDK disagrees with the service — a payload the API would
have accepted, rejected here. That is a bug worth reporting, and this is the escape hatch while it
is open. To drop one rule rather than all of them, see
[Validation: changing the rules](validation.md#changing-the-rules).

`SerializerConfig`
------------------

```php
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;

$client = new PolkurierWebservice(
    config: $config,
    serializerProvider: new SerializerProvider(new SerializerConfig(
        metadataCache: $psr6Pool,
        timezone: new DateTimeZone('Europe/Warsaw'),
    )),
);
```

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `metadataCache` | `?CacheItemPoolInterface` | `null` | PSR-6 pool for mapping metadata |
| `jsonEncodeOptions` | `int` | see below | Flags for `json_encode` on the request body |
| `timezone` | `?DateTimeZone` | `null` | The zone dates are read into |
| `rejectUnknownFields` | `bool` | `false` | Whether an unrecognised response field is an error |

### metadataCache

Reading the serializer attributes off around a hundred and fifty classes is the SDK's single
biggest start-up cost, and the result never changes between deployments of the same code. A
long-running process — a worker, a daemon, anything under FrankenPHP or RoadRunner — should hand
over a PSR-6 pool:

```php
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

new SerializerConfig(metadataCache: new PhpFilesAdapter('polkurier', 0, $cacheDir));
```

Remember to clear it on deploy; the metadata is keyed by class, not by version.

A short-lived request does not benefit from a shared pool nearly as much, because the cost is paid
lazily per class actually touched. Measure before adding one to a classic PHP-FPM setup.

### jsonEncodeOptions

The default is `SerializerConfig::DEFAULT_JSON_OPTIONS`:

```php
\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION
```

> **These options replace the default; they do not merge with it.** The first two are cosmetic —
> they are what makes a logged payload readable. The third is not: without
> `JSON_PRESERVE_ZERO_FRACTION`, a cash-on-delivery amount of `250.0` is written as `250`, and the
> API reads an integer where it expected a decimal. If you override this, OR your flags onto the
> constant rather than replacing it:
>
> ```php
> new SerializerConfig(
>     jsonEncodeOptions: SerializerConfig::DEFAULT_JSON_OPTIONS | \JSON_PRETTY_PRINT,
> );
> ```

### timezone

Left unset, dates arrive in whatever zone the API sent them with. Set it, and every
`DateTimeInterface` the SDK builds is converted into that zone as it is read:

```php
new SerializerConfig(timezone: new DateTimeZone('Europe/Warsaw'));
```

Worth doing in an application whose own clock is UTC, so that comparing a delivery date against a
local business day does not quietly go through a conversion nobody wrote.

This affects reading only. Dates you send are formatted by the per-property format declared on the
message, which is fixed by the protocol.

### rejectUnknownFields

By default an unrecognised field in a response is ignored, which is what you want in production: a
new field appearing on the other side should not break a working integration.

Turned on, it becomes an exception. That is worth doing in CI against a staging API, where it tells
you the response has grown a field — instead of that field going quietly unread until someone
notices the data is missing.

```php
new SerializerConfig(rejectUnknownFields: (bool)getenv('CI'));
```

Per-tenant credentials
----------------------

The services, the client factory and the request factory all resolve configuration through a
`ConfigProviderInterface` rather than holding a `Config` directly. The default implementation,
`ConfigProvider`, is the trivial case: configuration that is already known.

The indirection exists so credentials can be resolved at the moment they are needed — from a
container, a database row, or a tenant-scoped context:

```php
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;

final readonly class TenantConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private TenantContext $tenants,
        private CredentialStore $credentials,
    ) {}

    public function getConfig(): ConfigInterface
    {
        $tenant = $this->tenants->current();

        return new Config(
            authorization: $this->credentials->for($tenant),
            platform: 'my-platform',
            platformVersion: '1.0.0',
        );
    }
}

$client = new PolkurierWebservice(
    config: $fallbackConfig,
    configProvider: new TenantConfigProvider($tenants, $credentials),
);
```

When a provider is given, it overrides the `config` argument entirely.

One caveat: `AbstractService` and `ClientFactory` each call `getConfig()` once, in their
constructors, and hold the result. Services are created lazily on first use and then reused, so a
provider is consulted once per service per client instance — not once per call. For genuinely
per-request tenancy, build a client per tenant (they are cheap; the serializer is the expensive
part, and you can share one across clients).

Sharing a serializer across clients
-----------------------------------

```php
$serializer = new SerializerProvider(new SerializerConfig(metadataCache: $pool));

$clients = [];
foreach ($tenants as $tenant) {
    $clients[$tenant->id] = new PolkurierWebservice(
        config: $tenant->polkurierConfig(),
        serializerProvider: $serializer,
    );
}
```

`SerializerProvider` holds no per-call state, so one instance serves any number of clients. This is
the recommended shape for a multi-tenant application: one serializer, one client per tenant.

Full wiring reference
---------------------

Every collaborator `PolkurierWebservice` uses is optional and constructor-injected:

```php
new PolkurierWebservice(
    config: $config,                       // ConfigInterface, required
    logger: $logger,                       // ?LoggerInterface — defaults to NullLogger
    serializerProvider: $serializer,       // ?SerializerProviderInterface
    configProvider: $provider,             // ?ConfigProviderInterface — overrides $config
    clientFactory: $clientFactory,         // ?ClientFactoryInterface — your own PSR-18 client
    requestFactory: $requestFactory,       // ?RequestFactoryInterface
    responseFactory: $responseFactory,     // ?ResponseFactoryInterface
    validator: $validator,                 // ?ValidatorInterface
    services: [],                          // array<class-string, object> — ready-made services
);
```

What each of these is for, and when replacing one is a good idea, is in
[Architecture](architecture.md#extension-points).
