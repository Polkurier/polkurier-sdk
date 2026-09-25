Laravel
=======

For Laravel 10, 11 and 12. You need a service provider and a config file.

Configuration
-------------

`config/polkurier.php`:

```php
<?php

return [
    'login'    => env('POLKURIER_LOGIN'),
    'token'    => env('POLKURIER_TOKEN'),
    'sandbox'  => (bool)env('POLKURIER_SANDBOX', false),
    'url'      => env('POLKURIER_URL'),          // null in normal use
    'timeout'  => (int)env('POLKURIER_TIMEOUT', 30),
    'platform' => env('APP_NAME', 'laravel'),
    'version'  => '1.0.0',

    // Methods whose full request and response are logged. Bodies contain the API token.
    'log_methods' => [],
];
```

`.env`:

```dotenv
POLKURIER_LOGIN=12345
POLKURIER_TOKEN=
POLKURIER_SANDBOX=true
```

The service provider
--------------------

`app/Providers/PolkurierServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Service\CourierServiceInterface;
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Polkurier\Webservice\Service\CourierService;
use Polkurier\Webservice\Service\OrdersService;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

final class PolkurierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/polkurier.php', 'polkurier');

        $this->app->singleton(ConfigInterface::class, static function ($app): ConfigInterface {
            $c = $app['config']['polkurier'];

            return new Config(
                authorization: new AuthorizationMessage(token: $c['token'], login: $c['login']),
                platform: $c['platform'],
                platformVersion: $c['version'],
                apiUrl: $c['url'] ?: null,
                apiTimeout: $c['timeout'],
                sandbox: $c['sandbox'],
                methodsToLog: $c['log_methods'],
            );
        });

        $this->app->singleton(
            ConfigProviderInterface::class,
            static fn($app) => new ConfigProvider($app->make(ConfigInterface::class)),
        );

        // One serializer for the whole application. This is the expensive object.
        $this->app->singleton(SerializerProviderInterface::class, static function ($app) {
            return new SerializerProvider(new SerializerConfig(
                metadataCache: new Psr16Adapter($app->make(CacheRepository::class)->store()),
                rejectUnknownFields: $app->environment('testing'),
            ));
        });

        $this->app->singleton(
            ClientFactoryInterface::class,
            static fn($app) => new ClientFactory($app->make(ConfigProviderInterface::class)),
        );

        $this->app->singleton(RequestFactoryInterface::class, static fn($app) => new RequestFactory(
            $app->make(SerializerProviderInterface::class),
            $app->make(ConfigProviderInterface::class),
        ));

        $this->app->singleton(
            ResponseFactoryInterface::class,
            static fn($app) => new ResponseFactory($app->make(SerializerProviderInterface::class)),
        );

        $this->app->singleton(
            ValidatorInterface::class,
            static fn() => DefaultValidatorRegistryFactory::create(),
        );

        // The services. Register the ones you use. They all take the same 6 arguments.
        foreach ([
            OrdersServiceInterface::class  => OrdersService::class,
            CourierServiceInterface::class => CourierService::class,
        ] as $contract => $concrete) {
            $this->app->singleton($contract, static fn($app) => new $concrete(
                $app->make(RequestFactoryInterface::class),
                $app->make(ResponseFactoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(ClientFactoryInterface::class),
                $app->make(ConfigProviderInterface::class),
                $app->make(ValidatorInterface::class),
            ));
        }

        // The facade, for code that would rather have one object.
        $this->app->singleton(PolkurierWebservice::class, static fn($app) => new PolkurierWebservice(
            config: $app->make(ConfigInterface::class),
            logger: $app->make(LoggerInterface::class),
            serializerProvider: $app->make(SerializerProviderInterface::class),
            configProvider: $app->make(ConfigProviderInterface::class),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/polkurier.php' => config_path('polkurier.php'),
        ], 'polkurier-config');
    }
}
```

Register the provider in `bootstrap/providers.php` (Laravel 11+) or `config/app.php` (Laravel 10).

> `Psr16Adapter` comes from `symfony/cache`. It wraps Laravel's PSR-16 cache in the PSR-6 pool that
> [`SerializerConfig`](../../src/Serializer/SerializerConfig.php) expects. If you don't want that dependency, pass `metadataCache: null`.
> Everything still works, just without the metadata cache.

Using it
--------

Inject the interface in the constructor and let the container resolve it:

```php
namespace App\Shipping;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;

final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}
}
```

Or use the facade, if you registered it:

```php
app(PolkurierWebservice::class)->orders()->createOrder($message);
```

Adding a Laravel facade
-----------------------

```php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Polkurier\Webservice\PolkurierWebservice;

/**
 * @method static \Polkurier\Webservice\Api\Service\OrdersServiceInterface orders()
 * @method static \Polkurier\Webservice\Api\Service\CourierServiceInterface courier()
 *
 * @see PolkurierWebservice
 */
final class Polkurier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PolkurierWebservice::class;
    }
}
```

```php
Polkurier::orders()->getStatus(new GetStatusMessage(orderNo: 'POL123456789'));
```

A facade is convenient but harder to test than constructor injection. It's fine in Artisan
commands and one-off scripts. In classes with logic you want to test, inject the interface.

Jobs: placing orders off the request
------------------------------------

```php
namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

final class PlaceShipment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(private readonly Order $order) {}

    public function handle(OrdersServiceInterface $orders, ShipmentMapper $mapper): void
    {
        if ($this->order->polkurier_number !== null) {
            return;   // Already placed. Queue delivery is at-least-once.
        }

        try {
            $created = $orders->createOrder($mapper->toMessage($this->order));
        } catch (ValidationException | WebserviceException $e) {
            // The payload or the account is wrong. Retrying will not fix it.
            $this->fail($e);

            return;
        } catch (ConnectionException) {
            // Unknown outcome. Retrying is safe only because of the guard above.
            $this->release(30);

            return;
        }

        $this->order->forceFill(['polkurier_number' => $created->getOrderNumber()])->save();
    }
}
```

Note the [`Illuminate\Queue\InteractsWithQueue::fail()`](https://github.com/laravel/framework/blob/12.x/src/Illuminate/Queue/InteractsWithQueue.php) call in place of a rethrow. A rejected payload will be rejected again.
If you rethrow, the job uses up all its retries and ends up in `failed_jobs`, where it looks like
an outage.

Caching stable reads
--------------------

```php
use Illuminate\Support\Facades\Cache;

$lockers = Cache::remember(
    'polkurier.inpost.lockers',
    now()->addHour(),
    fn() => app(CourierServiceInterface::class)->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT'])),
);
```

[Recipes: caching](../recipes.md#caching-what-does-not-change) lists what to cache and for how
long.

Validation errors in a form request
-----------------------------------

```php
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Polkurier\Webservice\Exception\ValidationException;

try {
    $created = $orders->createOrder($message);
} catch (ValidationException $e) {
    throw LaravelValidationException::withMessages(
        collect($e->getValidationErrors())
            ->mapWithKeys(fn(array $messages, string $field) => [
                $fieldMap[$field] ?? $field => $messages,
            ])
            ->all(),
    );
}
```

`$fieldMap` maps the API's wire names (`sender.postcode`) to your form's field names
(`shipping.postal_code`). The same code works for a rejection from the API, because
[`WebserviceException::getErrorMessage()->getValidationErrors()`](../../src/Exception/WebserviceException.php) returns the same shape.

Per-tenant credentials
----------------------

Bind [`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php) to a class that reads the current tenant:

```php
$this->app->singleton(ConfigProviderInterface::class, static fn($app) => new class($app) implements ConfigProviderInterface {
    public function __construct(private readonly $app) {}

    public function getConfig(): ConfigInterface
    {
        $tenant = $this->app->make(TenantContext::class)->current();

        return new Config(
            authorization: new AuthorizationMessage($tenant->polkurier_token, $tenant->polkurier_login),
            platform: config('polkurier.platform'),
            platformVersion: config('polkurier.version'),
        );
    }
});
```

> **In this setup, don't register the services as singletons.** Each service reads its
> configuration once, in the constructor. A singleton built for tenant A keeps tenant A's
> credentials until the process ends. Register the services with [`Illuminate\Container\Container::bind()`](https://github.com/laravel/framework/blob/12.x/src/Illuminate/Container/Container.php)
> instead of [`Container::singleton()`](https://github.com/laravel/framework/blob/12.x/src/Illuminate/Container/Container.php). [`SerializerProviderInterface`](../../src/Serializer/SerializerProviderInterface.php) can stay a singleton, since it is stateless and
> expensive to build. Under Octane, also reset anything tenant-scoped between requests.

Artisan commands
----------------

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Polkurier\Webservice\Api\Service\UtilsServiceInterface;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

final class PolkurierPing extends Command
{
    protected $signature = 'polkurier:ping';
    protected $description = 'Check that the Polkurier API is reachable and the credentials work';

    public function handle(UtilsServiceInterface $utils): int
    {
        try {
            $this->info('API time: ' . $utils->heartbeat()->getTime()->format('c'));
        } catch (ConnectionException $e) {
            $this->error('Unreachable: ' . $e->getMessage());

            return self::FAILURE;
        }

        try {
            $utils->testAuthApi(new AuthorizationMessage(
                token: config('polkurier.token'),
                login: config('polkurier.login'),
            ));
            $this->info('Credentials accepted.');
        } catch (AuthorizationException $e) {
            $this->error('Credentials rejected: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
```

Testing
-------

Replace the transport in the container:

```php
use Polkurier\Webservice\Http\ClientFactoryInterface;

use Polkurier\Webservice\Testing\RecordingClient;

$fake = new RecordingClient('{"status":"success","response":true}');
$this->app->instance(ClientFactoryInterface::class, $fake);
```

If you only want to test your own code, mock the service instead:

```php
$this->mock(OrdersServiceInterface::class)
    ->shouldReceive('createOrder')
    ->once()
    ->andReturn($cannedCreatedOrder);
```

[Testing](../testing.md) explains which approach fits which kind of test.
