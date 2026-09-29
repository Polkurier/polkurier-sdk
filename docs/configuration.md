Configuration
=============

The SDK has two configuration objects.

[`Config`](../src/Config.php) covers the API: the endpoint, your credentials, the timeout and logging.
[`SerializerConfig`](../src/Serializer/SerializerConfig.php) covers your application: caching, the timezone dates are read into, and whether
unknown response fields are an error. The two are independent. If your application runs two
clients against two accounts, they will usually share one serializer.

You can supply the configuration in three ways, depending on when the values are known:

1. A [`Config`](../src/Config.php) object, built once and passed to the client. This is the usual case, described
   below.
2. Your own [`ConfigInterface`](../src/Api/ConfigInterface.php) implementation, when the values come from somewhere else, e.g. a
   settings table, a framework's parameter bag or a feature flag. You can also extend [`Config`](../src/Config.php) and
   override the getters you need.
3. Your own [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php), when the configuration isn't known until it is used, e.g.
   per tenant, per shop or per job. See [per-tenant credentials](#per-tenant-credentials).

These can be combined. A provider returns a [`ConfigInterface`](../src/Api/ConfigInterface.php), which can be a plain [`Config`](../src/Config.php).

[`Config`](../src/Config.php)
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

Three arguments are required. The others have defaults.

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `authorization` | [`AuthorizationMessage`](../src/Http/Request/AuthorizationMessage.php) | required | Customer id and API token |
| `platform` | `string` | required | Name identifying your integration |
| `platformVersion` | `string` | required | Its version |
| `apiUrl` | `?string` | `null` | Overrides the endpoint entirely |
| `apiTimeout` | `int` | `30` | Request timeout, in seconds |
| `sandbox` | `bool` | `false` | Targets the sandbox host |
| `loggingEnabled` | `bool` | `true` | Enables error logging |
| `methodsToLog` | [`ApiMethodInterface`](../src/Enum/Http/ApiMethodInterface.php)`[]` | `[]` | Methods whose full traffic is logged |
| `validatePayloads` | `bool` | `true` | Check payloads before sending them |

[`Config`](../src/Config.php) is `readonly` and implements [`Api\ConfigInterface`](../src/Api/ConfigInterface.php). If the configuration needs to change
at runtime, implement the interface or use a different provider.

### Choosing an endpoint

If you set `apiUrl`, it is used. Otherwise `sandbox` picks one of the two constants on [`Config`](../src/Config.php):

```php
Config::API_URL         // https://api.polkurier.pl
Config::SANDBOX_API_URL // https://api-sandbox.polkurier.pl
```

For development, use `sandbox: true`. Use `apiUrl` for any other host, such as a local mirror or
a staging server.

```php
new Config(
    authorization: $auth,
    platform: 'my-shop',
    platformVersion: '1.0.0',
    apiUrl: getenv('POLKURIER_API_URL') ?: null,
    sandbox: (bool)getenv('POLKURIER_SANDBOX'),
);
```

Note: every log record the SDK writes includes `sandbox`, so you can see in the log which
endpoint a request went to.

### Timeouts

`apiTimeout` is passed to Guzzle as its `timeout` option and covers the whole request. 30 seconds
is enough for most calls, but a few can take longer. `get_label` with 12 order numbers renders 12
labels into one PDF, and `get_orders` with `packs`, `items` and `files` requested returns a large
response. Raise the timeout if you send batches like these.

A timeout throws a [`ConnectionException`](../src/Exception/ConnectionException.php), the same class as DNS and TLS failures. The SDK can't
tell whether the order was placed before the timeout, only that no answer arrived. See
[Recipes: retrying safely](recipes.md#retrying-safely).

### Logging

Nothing is logged unless you pass a PSR-3 logger:

```php
$client = new PolkurierWebservice($config, $logger);
```

There are two levels of logging, each with its own setting.

Error logging is controlled by `loggingEnabled`. It logs API errors and transport failures: the
failed request, its body, and the response, if any. It is on by default, which has no effect until
you attach a logger.

Extended logging also logs successful calls, with the full request and response. You turn it on
per method:

```php
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;

new Config(
    // …
    methodsToLog: [ApiMethodEnum::CreateOrder, ApiMethodEnum::CancelOrder],
);
```

[`ConfigInterface::isExtendedLoggingEnabled()`](../src/Api/ConfigInterface.php) checks both settings. If `loggingEnabled` is off, nothing is logged,
whatever `methodsToLog` contains.

> **Request bodies contain the API token.** [`AuthorizationMessage`](../src/Http/Request/AuthorizationMessage.php) is included in every
> authenticated request, and the logger receives the body unchanged. Send the logs somewhere you are
> OK with storing credentials, or filter the record first. The SDK does not redact anything.

Each record also has structured context (`method`, `sandbox`, `payload`, `request`,
`parsed_response`, `response`), so a processor can read those fields directly without parsing the
message.

### Payload validation

`validatePayloads` controls whether the SDK checks a message before sending it. It is on by
default. When it is off, the validator isn't called at all.

Only turn it off if the SDK rejects a payload that the API would accept. Please report that as a
bug. To drop a single rule instead of all of them, see
[Validation: changing the rules](validation.md#changing-the-rules).

[`SerializerConfig`](../src/Serializer/SerializerConfig.php)
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

Reading the serializer attributes of around 150 classes is the SDK's biggest start-up cost, and
the result doesn't change between deployments of the same code. In a long-running process (a
worker, a daemon, or anything under FrankenPHP or RoadRunner), pass a PSR-6 pool:

```php
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

new SerializerConfig(metadataCache: new PhpFilesAdapter('polkurier', 0, $cacheDir));
```

Clear the cache on deploy. The metadata is keyed by class, not by version.

Short-lived requests benefit much less, because metadata is only loaded for the classes a request
uses. Measure before adding a cache to a classic PHP-FPM setup.

### jsonEncodeOptions

The default is [`SerializerConfig::DEFAULT_JSON_OPTIONS`](../src/Serializer/SerializerConfig.php):

```php
\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION
```

> **Your options replace the default. They are not merged with it.** The first two flags only make
> logged payloads easier to read. The third one matters: without `JSON_PRESERVE_ZERO_FRACTION`, a
> cash-on-delivery amount of `250.0` is written as `250`. Floats must stay floats on the wire, so
> keep this flag. To add flags, OR them onto the constant:
>
> ```php
> new SerializerConfig(
>     jsonEncodeOptions: SerializerConfig::DEFAULT_JSON_OPTIONS | \JSON_PRETTY_PRINT,
> );
> ```

### timezone

If this is not set, dates keep the timezone the API sent. If it is set, every [`DateTimeInterface`](https://www.php.net/manual/en/class.datetimeinterface.php)
the SDK creates is converted to that zone:

```php
new SerializerConfig(timezone: new DateTimeZone('Europe/Warsaw'));
```

This is useful if your application runs in UTC and you compare delivery dates against local
business days.

It only affects dates you read. Dates you send use the format declared on each message property,
which is fixed by the protocol.

### rejectUnknownFields

By default, unknown fields in a response are ignored. That is the right setting for production,
where a new field in the API shouldn't break your integration.

When this option is on, an unknown field throws an exception. Turn it on in CI against a staging
API to find out when a response gains a field the SDK doesn't read.

```php
new SerializerConfig(rejectUnknownFields: (bool)getenv('CI'));
```

Per-tenant credentials
----------------------

The services, the client factory and the request factory get their configuration from a
[`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php) instead of holding a [`Config`](../src/Config.php) directly. The default implementation,
[`ConfigProvider`](../src/ConfigProvider.php), just returns a configuration that is already known.

With your own provider, you can look up credentials when they are needed, e.g. from a container, a
database row or a tenant context:

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

If you pass a provider, the `config` argument is ignored.

The provider is asked on every call. [`AbstractService`](../src/Service/AbstractService.php), [`RequestFactory`](../src/Http/Request/RequestFactory.php) and
[`ClientFactory`](../src/Http/ClientFactory.php) all call [`ConfigProviderInterface::getConfig()`](../src/Api/ConfigProviderInterface.php) when a request is made and
don't keep the result. One client can serve every tenant, as long as the provider can tell which
tenant the current call is for. Where it can't (e.g. in a queue worker with no current request),
build a client per tenant instead. Clients are inexpensive to create. The serializer is the costly
part, and you can share it between clients.

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

[`SerializerProvider`](../src/Serializer/SerializerProvider.php) has no per-call state, so one instance can serve any number of clients. For
a multi-tenant application, share one serializer, and use either a per-tenant provider or one
client per tenant.

Full wiring reference
---------------------

All of [`PolkurierWebservice`](../src/PolkurierWebservice.php)'s collaborators are optional constructor arguments:

```php
new PolkurierWebservice(
    config: $config,                       // ConfigInterface, required
    logger: $logger,                       // ?LoggerInterface, defaults to NullLogger
    serializerProvider: $serializer,       // ?SerializerProviderInterface
    configProvider: $provider,             // ?ConfigProviderInterface, overrides $config
    clientFactory: $clientFactory,         // ?ClientFactoryInterface, for your own PSR-18 client
    requestFactory: $requestFactory,       // ?RequestFactoryInterface
    responseFactory: $responseFactory,     // ?ResponseFactoryInterface
    validator: $validator,                 // ?ValidatorInterface
    services: [],                          // array<class-string, object>: ready-made services
);
```

See [Architecture](architecture.md#extension-points) for what each one does and when to replace
it.
