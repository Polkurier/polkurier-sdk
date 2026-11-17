Configuration
=============

The SDK has two configuration objects.

[`Config`](../src/Config.php) covers the API: the endpoint, your credentials, the timeout and logging.
[`SerializerConfig`](../src/Serializer/SerializerConfig.php) covers your application: caching, the timezone for dates without an offset, and whether
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
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;

$config = new Config(
    authorization: new AuthorizationMessage(token: '…', login: '12345'),
    platform: 'my-shop',
    platformVersion: '1.0.0',
);
```

Three arguments are required. The others have defaults.

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `authorization` | [`AuthorizationMessage`](../src/Dto/Request/AuthorizationMessage.php) | required | Customer id and API token |
| `platform` | `string` | required | Name identifying your integration |
| `platformVersion` | `string` | required | Its version |
| `apiUrl` | `?string` | `null` | Overrides the endpoint entirely |
| `apiTimeout` | `int` | `30` | Request timeout, in seconds |
| `sandbox` | `bool` | `false` | Targets the sandbox host |
| `loggingEnabled` | `bool` | `true` | Enables error logging |
| `methodsToLog` | [`ApiMethodInterface`](../src/Api/Http/ApiMethodInterface.php)`[]` | `[]` | Methods whose full traffic is logged |
| `validatePayloads` | `bool` | `true` | Check payloads before sending them |
| `logSensitiveData` | `bool` | `false` | Log secrets as they are instead of hiding them |
| `logPersonalData` | `bool` | `false` | Log personal data as it is instead of hiding it |
| `logFiles` | `bool` | `false` | Save files from requests and responses, and point to them from the log |
| `logFilesDirectory` | `?string` | `null` | Where to save them. Defaults to a directory in the system's temporary directory |

[`Config`](../src/Config.php) is `readonly` and implements [`Api\ConfigInterface`](../src/Api/ConfigInterface.php). If the configuration needs to change
at runtime, implement the interface or use a different provider.

### Choosing an endpoint

If you set `apiUrl`, it is used. Otherwise `sandbox` picks one of the two constants on [`Config`](../src/Config.php):

```php
Config::API_URL         // https://api.polkurier.pl
Config::SANDBOX_API_URL // https://api-sandbox.polkurier.pl
```

`sandbox: true` points the client at the sandbox. `apiUrl` points it at any other host, such as a
local mirror or a staging server.

```php
new Config(
    authorization: $auth,
    platform: 'my-shop',
    platformVersion: '1.0.0',
    apiUrl: getenv('POLKURIER_API_URL') ?: null,
    sandbox: (bool)getenv('POLKURIER_API_SANDBOX'),
);
```

Every log record the SDK writes has the `sandbox` flag in its context.

### Timeouts

`apiTimeout` is passed to Guzzle as its `timeout` option and covers the whole request. 30 seconds
is enough for most calls, but a few can take longer. `get_label` with 12 order numbers renders 12
labels into one PDF, and `get_orders` with `packs`, `items` and `files` requested returns a large
response. Batches like these may need a longer timeout.

A timeout throws a [`ConnectionException`](../src/Exception/ConnectionException.php), the same class as DNS and TLS failures. The SDK can't
tell whether the order was placed before the timeout, only that no answer arrived. See
[Recipes: retrying safely](recipes.md#retrying-safely).

### Logging

Nothing is logged unless you pass a PSR-3 logger:

```php
$client = new PolkurierWebservice($config, $logger);
```

Error logging is controlled by `loggingEnabled`. It logs API errors and transport failures: the
failed request, its body, and the response, if any. It also logs a warning for each way a response
differed from what the SDK expects: an unknown field, a missing one, a value of another type, and
an enum value the SDK doesn't know (see [enums](messages.md#enums)). It is on by default, which has
no effect until you attach a logger.

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

Each record also has structured context (`method`, `sandbox`, `payload`, `request`,
`parsed_response`, `response`), so a processor can read those fields directly without parsing the
message.

#### Secrets, personal data and files

Requests and responses carry things that don't belong in a log by default. Before anything is
logged, the SDK prepares each body:

- **Secrets** are hidden: the API token, which is in every authenticated request, and passwords,
  PINs, two-factor codes and access tokens. The fields are [`SensitiveDataRedactor::FIELDS`](../src/Log/SensitiveDataRedactor.php), and
  any key named `pin` or ending in `_pin`.
- **Personal data** is hidden: names, street addresses, email addresses, phone numbers, tax and
  company registration numbers, and bank account numbers. The fields are
  [`PersonalDataRedactor::FIELDS`](../src/Log/PersonalDataRedactor.php), plus a few generic keys that only hold personal data in some methods,
  such as a bank account's `number` ([`PersonalDataRedactor::METHOD_FIELDS`](../src/Log/PersonalDataRedactor.php)).
- **Files**, such as labels, protocols, invoices and attachments, are taken out. Each one is
  replaced with a note of its size, e.g. `[file left out of the log: 48213 bytes]`.

Hidden values are replaced with `***`. The login is kept, since it identifies the account without
giving access to it. In the context, `request` and `response` are copies prepared the same way.
`payload` and `parsed_response` are objects that can't be prepared, so they are left out unless
both secrets and personal data may be logged. Their contents are in the request and response
bodies either way.

Each of these can be turned off separately when you need it, e.g. to reproduce a call exactly:

```php
new Config(
    // …
    logSensitiveData: true,
    logPersonalData: true,
    logFiles: true,
    logFilesDirectory: '/var/log/my-shop/polkurier-files',
);
```

With `logFiles` on, each file is saved in `logFilesDirectory` under a name made of the time, the
method, a random part and the file's own name, e.g. `20261005T101500-get_label-1a2b3c4d-file.pdf`.
The log entry says where: `[file saved to /var/log/my-shop/polkurier-files/20261005T101500-get_label-1a2b3c4d-file.pdf: 48213 bytes]`.
The directory is created if it doesn't exist. If a file can't be saved, the note says so and the
entry is logged anyway. The SDK never deletes saved files.
Every saved file's name matches [`LoggedFileWriter::FILE_NAME_PATTERN`](../src/Log/LoggedFileWriter.php), so a cleanup job can recognise them
without touching anything else in the directory:

```php
use Polkurier\Webservice\Log\LoggedFileWriter;

foreach (new DirectoryIterator($directory) as $file) {
    if ($file->isFile()
        && preg_match(LoggedFileWriter::FILE_NAME_PATTERN, $file->filename)
        && $file->getMTime() < strtotime('-7 days')
    ) {
        unlink($file->getPathname());
    }
}
```

Such logs and files are as sensitive as what's in them. A label, for example, shows the recipient's
name and address even with `logPersonalData` off. Exceptions in the context (`exception`,
`client_exception`) are never altered, and some of them hold the full request or response, so a
logger that serializes exceptions in depth can still record what was hidden.

### Payload validation

`validatePayloads` controls whether the SDK checks a message before sending it. It is on by
default. When it is off, the validator isn't called at all.

It exists for the case where the SDK rejects a payload that the API would accept, which is a bug in
the SDK. A single rule can be dropped instead of all of them, see
[Validation: changing the rules](validation.md#changing-the-rules).

[`SerializerConfig`](../src/Serializer/SerializerConfig.php)
------------------

```php
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerConfig;

$client = new PolkurierWebservice(
    config: $config,
    serializerConfig: new SerializerConfig(
        metadataCache: $psr6Pool,
        timezone: new DateTimeZone('Europe/Warsaw'),
    ),
);
```

| Argument | Type | Default | Meaning |
|----------|------|---------|---------|
| `metadataCache` | `?CacheItemPoolInterface` | `null` | PSR-6 pool for mapping metadata |
| `jsonEncodeOptions` | `int` | see below | Flags for `json_encode` on the request body |
| `timezone` | `?DateTimeZone` | `null` | The zone a date without an offset is read in |
| `strictResponses` | `bool` | `false` | Whether a response that differs from the SDK is an error |

The four are [`Api\SerializerConfigInterface`](../src/Api/SerializerConfigInterface.php), which [`PolkurierWebservice`](../src/PolkurierWebservice.php), [`OrderReader`](../src/Webhook/OrderReader.php) and
[`SerializerProvider`](../src/Serializer/SerializerProvider.php) take. Implement it to read them from a settings store or a parameter
bag, as [`ConfigInterface`](../src/Api/ConfigInterface.php) is implemented for the API's own settings.

### metadataCache

Reading the serializer attributes of around 150 classes is the SDK's biggest start-up cost, and
the result doesn't change between deployments of the same code. In a long-running process (a
worker, a daemon, or anything under FrankenPHP or RoadRunner), pass a PSR-6 pool:

```php
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

new SerializerConfig(metadataCache: new PhpFilesAdapter('polkurier', 0, $cacheDir));
```

The metadata is keyed by class, not by version, so a deploy that changes the classes needs the cache
cleared.

Short-lived requests benefit much less, because metadata is only loaded for the classes a request
uses.

### jsonEncodeOptions

The default is [`SerializerConfig::DEFAULT_JSON_OPTIONS`](../src/Serializer/SerializerConfig.php):

```php
\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION
```

> **Your options replace the default. They are not merged with it.** The first two flags only make
> logged payloads easier to read. The third one matters: without `JSON_PRESERVE_ZERO_FRACTION`, a
> cash-on-delivery amount of `250.0` is written as `250`. Flags are added by ORing them onto the
> constant:
>
> ```php
> new SerializerConfig(
>     jsonEncodeOptions: SerializerConfig::DEFAULT_JSON_OPTIONS | \JSON_PRETTY_PRINT,
> );
> ```

### timezone

Most dates the API sends carry their offset, and they keep it. The rest, such as a collection date
or a pickup time window, have none: they are local Polish time, and they are read in this zone, or
in PHP's default timezone when it is not set. If that default is anything but Europe/Warsaw, set
it:

```php
new SerializerConfig(timezone: new DateTimeZone('Europe/Warsaw'));
```

Nothing is converted. To show a date in another zone, call its
[`DateTimeImmutable::setTimezone()`](https://www.php.net/manual/en/datetimeimmutable.settimezone.php).

It only affects dates you read. Dates you send use the format declared on each message property,
which is fixed by the protocol.

### strictResponses

By default, a response that differs from the SDK still reads, so a change to the API doesn't break
a working integration: an unknown field is ignored, a missing field gets an empty value, a value of
another type is converted. Each difference is recorded and logged, see
[Serialization](serialization.md#responses-that-differ-from-the-sdk).

When this option is on, such a response throws an exception instead, except that an unknown enum
value still reads as null. Against a staging API in CI, that shows when a response changes.

```php
new SerializerConfig(strictResponses: (bool)getenv('CI'));
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

The SDK calls [`ConfigProviderInterface::getConfig()`](../src/Api/ConfigProviderInterface.php) each time it makes a request, and doesn't keep the
result. One client can serve every tenant, as long as the provider can tell which
tenant the current call is for. Where it can't (e.g. in a queue worker with no current request),
build a client per tenant instead. Clients are inexpensive to create. The serializer is the costly
part, and you can share it between clients.

Sharing a serializer across clients
-----------------------------------

```php
$serializerConfig = new SerializerConfig(metadataCache: $pool);

$clients = [];
foreach ($tenants as $tenant) {
    $clients[$tenant->id] = new PolkurierWebservice(
        config: $tenant->polkurierConfig(),
        serializerConfig: $serializerConfig,
    );
}
```

Each client builds its own serializer from that configuration. The PSR-6 pool is what they share,
so the mapping metadata is read once however many tenants there are.

Full wiring reference
---------------------

All of [`PolkurierWebservice`](../src/PolkurierWebservice.php)'s collaborators are optional constructor arguments:

```php
new PolkurierWebservice(
    config: $config,                       // ConfigInterface, required
    logger: $logger,                       // ?LoggerInterface, defaults to NullLogger
    serializerConfig: $serializerConfig,   // ?SerializerConfigInterface, reading and writing bodies
    configProvider: $provider,             // ?ConfigProviderInterface, overrides $config
    clientFactory: $clientFactory,         // ?ClientFactoryInterface, for your own PSR-18 client
    validator: $validator,                 // ?ValidatorInterface
    services: [],                          // array<class-string, object>: ready-made services
);
```

See [Architecture](architecture.md#extension-points) for what each one does and when to replace
it.
