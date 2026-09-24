Symfony
=======

Symfony 6.4 / 7.x. Everything here is plain service configuration — there is no bundle to install.

Configuration
-------------

Credentials come from the environment. `.env`:

```dotenv
POLKURIER_LOGIN=12345
POLKURIER_TOKEN=
POLKURIER_SANDBOX=1
```

Keep the real token in `.env.local` or your secrets vault, never in `.env`:

```bash
php bin/console secrets:set POLKURIER_TOKEN
```

Wiring
------

`config/services.yaml`:

```yaml
parameters:
    polkurier.platform: 'my-shop'
    polkurier.platform_version: '1.0.0'

services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Credentials.
    Polkurier\Webservice\Http\Request\AuthorizationMessage:
        arguments:
            $token: '%env(POLKURIER_TOKEN)%'
            $login: '%env(POLKURIER_LOGIN)%'

    Polkurier\Webservice\Config:
        arguments:
            $authorization: '@Polkurier\Webservice\Http\Request\AuthorizationMessage'
            $platform: '%polkurier.platform%'
            $platformVersion: '%polkurier.platform_version%'
            $sandbox: '%env(bool:POLKURIER_SANDBOX)%'
            $apiTimeout: 30
    Polkurier\Webservice\Api\ConfigInterface: '@Polkurier\Webservice\Config'

    Polkurier\Webservice\ConfigProvider: ~
    Polkurier\Webservice\Api\ConfigProviderInterface: '@Polkurier\Webservice\ConfigProvider'

    # One serializer, cached. This is the expensive object.
    Polkurier\Webservice\Serializer\SerializerConfig:
        arguments:
            $metadataCache: '@cache.polkurier'
            $rejectUnknownFields: '%kernel.debug%'

    Polkurier\Webservice\Serializer\SerializerProvider: ~
    Polkurier\Webservice\Serializer\SerializerProviderInterface: '@Polkurier\Webservice\Serializer\SerializerProvider'

    # Transport and envelope.
    Polkurier\Webservice\Http\ClientFactory: ~
    Polkurier\Webservice\Http\ClientFactoryInterface: '@Polkurier\Webservice\Http\ClientFactory'

    Polkurier\Webservice\Http\Request\RequestFactory: ~
    Polkurier\Webservice\Http\Request\RequestFactoryInterface: '@Polkurier\Webservice\Http\Request\RequestFactory'

    Polkurier\Webservice\Http\Response\ResponseFactory: ~
    Polkurier\Webservice\Http\Response\ResponseFactoryInterface: '@Polkurier\Webservice\Http\Response\ResponseFactory'

    # Payload validation.
    Polkurier\Webservice\Api\Validation\ValidatorInterface:
        factory: ['Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory', 'create']

    # The services, bound to their interfaces.
    Polkurier\Webservice\Service\:
        resource: '../vendor/polkurier/polkurier-sdk/src/Service/*Service.php'
        arguments:
            $logger: '@monolog.logger.polkurier'

    Polkurier\Webservice\Api\Service\OrdersServiceInterface: '@Polkurier\Webservice\Service\OrdersService'
    Polkurier\Webservice\Api\Service\CourierServiceInterface: '@Polkurier\Webservice\Service\CourierService'
    Polkurier\Webservice\Api\Service\AddressesServiceInterface: '@Polkurier\Webservice\Service\AddressesService'
    # … and the rest, as you need them.
```

`config/packages/cache.yaml`:

```yaml
framework:
    cache:
        pools:
            cache.polkurier:
                adapter: cache.adapter.filesystem
                default_lifetime: 0
```

Metadata is keyed by class, not by version, so clear the pool on deploy:

```bash
php bin/console cache:pool:clear cache.polkurier
```

`config/packages/monolog.yaml`:

```yaml
monolog:
    channels: ['polkurier']
    handlers:
        polkurier:
            type: rotating_file
            path: '%kernel.logs_dir%/polkurier.log'
            level: error
            channels: ['polkurier']
```

> Keep that handler at `error` unless you have somewhere safe for it. Setting `methodsToLog` on
> `Config` logs full request bodies, and those contain the API token.

Autowiring
----------

With the above, a service just asks for what it needs:

```php
namespace App\Shipping;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Response\Order\CreatedOrderMessage;

final readonly class ShipmentDispatcher
{
    public function __construct(private OrdersServiceInterface $orders) {}

    public function dispatch(CreateOrderMessage $message): CreatedOrderMessage
    {
        return $this->orders->createOrder($message);
    }
}
```

This is the shape to aim for: one interface per collaborator, nothing that knows the SDK has a
facade.

The facade instead
------------------

If you would rather have one service than sixteen, register `PolkurierWebservice` and skip the
per-service aliases:

```yaml
    Polkurier\Webservice\PolkurierWebservice:
        arguments:
            $config: '@Polkurier\Webservice\Api\ConfigInterface'
            $logger: '@monolog.logger.polkurier'
            $serializerProvider: '@Polkurier\Webservice\Serializer\SerializerProviderInterface'
```

Everything else it needs is optional and built internally. It is a legitimate choice for an
application that touches most of the API; it is the wrong one for a module that places orders.

A bundle-shaped alternative
---------------------------

For anything reused across projects, put the above in a small bundle extension instead of
`services.yaml`:

```php
namespace App\Polkurier\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class PolkurierExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('polkurier.platform', $config['platform']);
        $container->setParameter('polkurier.sandbox', $config['sandbox']);

        (new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config')))
            ->load('services.yaml');
    }
}
```

Per-tenant credentials
----------------------

Where credentials vary per user, per shop or per request, implement `ConfigProviderInterface` and
alias it in place of `ConfigProvider`:

```php
namespace App\Polkurier;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

final readonly class TenantConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private TenantContext $tenants,
        private string $platform,
        private string $platformVersion,
    ) {}

    public function getConfig(): ConfigInterface
    {
        $tenant = $this->tenants->current();

        return new Config(
            authorization: new AuthorizationMessage($tenant->polkurierToken, $tenant->polkurierLogin),
            platform: $this->platform,
            platformVersion: $this->platformVersion,
        );
    }
}
```

```yaml
    Polkurier\Webservice\Api\ConfigProviderInterface: '@App\Polkurier\TenantConfigProvider'
```

One caveat from [Configuration](../configuration.md#per-tenant-credentials): services resolve
configuration once, in their constructors. In a classic request-per-process Symfony application
that is per request and therefore fine. Under Messenger workers, FrankenPHP or RoadRunner it is
**not** — the container is reused across messages, and a service built for tenant A will keep
tenant A's credentials.

Two ways out of that. Make the services lazy and reset them between messages with a
[service reset](https://symfony.com/doc/current/messenger.html), or build the client per message:

```php
#[AsMessageHandler]
final readonly class PlaceShipmentHandler
{
    public function __construct(
        private CredentialStore $credentials,
        private SerializerProviderInterface $serializer,   // shared; this one is safe
    ) {}

    public function __invoke(PlaceShipment $message): void
    {
        $client = new PolkurierWebservice(
            config: $this->credentials->configFor($message->tenantId),
            serializerProvider: $this->serializer,
        );

        $client->orders()->createOrder($this->map($message));
    }
}
```

The serializer is the expensive part and it is stateless, so sharing it while rebuilding the client
gets you both correctness and speed.

Messenger: placing orders off the request
-----------------------------------------

```php
namespace App\Shipping\Message;

final readonly class PlaceShipment
{
    public function __construct(public int $orderId) {}
}
```

```php
namespace App\Shipping\MessageHandler;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final readonly class PlaceShipmentHandler
{
    public function __construct(
        private OrdersServiceInterface $orders,
        private OrderRepository $repository,
        private ShipmentMapper $mapper,
    ) {}

    public function __invoke(PlaceShipment $message): void
    {
        $order = $this->repository->get($message->orderId);

        if ($order->polkurierNumber !== null) {
            return;   // Already placed. Messenger retries are at-least-once.
        }

        try {
            $created = $this->orders->createOrder($this->mapper->toMessage($order));
        } catch (ValidationException | WebserviceException $e) {
            // The payload or the account is wrong. Retrying will not fix it.
            throw new UnrecoverableMessageHandlingException($e->getMessage(), previous: $e);
        } catch (ConnectionException $e) {
            // Unknown outcome. Let Messenger retry — but only because the guard above
            // makes a second attempt safe once the number is stored.
            throw $e;
        }

        $order->polkurierNumber = $created->getOrderNumber();
        $this->repository->save($order);
    }
}
```

Two things that matter here. `UnrecoverableMessageHandlingException` stops Messenger retrying a
payload that will never be accepted — without it a bad postcode burns three retries and lands in
the failure transport looking like an outage. And the guard at the top is what makes retrying a
`ConnectionException` safe; there is no idempotency key, so the only protection is your own record
of having placed it. A stricter version reconciles against `get_orders` instead — see
[Errors](../errors.md#retrying-safely).

Configure the retry policy to match:

```yaml
framework:
    messenger:
        transports:
            polkurier:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 5000
                    multiplier: 3
```

Caching stable reads
--------------------

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class PickupPoints
{
    public function __construct(
        private CourierServiceInterface $courier,
        private CacheInterface $cache,
    ) {}

    /** @return CourierPoint[] */
    public function inpostLockers(): array
    {
        return $this->cache->get('polkurier.inpost.lockers', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return $this->courier->findCourierPoints(new GetPointMessage(couriers: ['INPOST_PACZKOMAT']));
        });
    }
}
```

Health check
------------

```php
namespace App\Health;

use Polkurier\Webservice\Api\Service\UtilsServiceInterface;
use Polkurier\Webservice\Exception\Exception as PolkurierException;

final readonly class PolkurierHealthCheck
{
    public function __construct(private UtilsServiceInterface $utils) {}

    public function __invoke(): bool
    {
        try {
            $this->utils->heartbeat();

            return true;
        } catch (PolkurierException) {
            return false;
        }
    }
}
```

`heartbeat()` sends no credentials, so it answers whether the API is reachable whatever state the
token is in.

Validation errors in a form
---------------------------

`ValidationException` reports against wire field names, which will not match your form's field
names. Map them once, at the edge:

```php
use Polkurier\Webservice\Exception\ValidationException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

function applyToForm(ValidationException $e, FormInterface $form, array $fieldMap): void
{
    foreach ($e->getValidationErrors() as $field => $messages) {
        $target = $fieldMap[$field] ?? null;

        foreach ($messages as $message) {
            $target !== null && $form->has($target)
                ? $form->get($target)->addError(new FormError($message))
                : $form->addError(new FormError(sprintf('%s %s', $field, $message)));
        }
    }
}
```

The same function handles an API-side rejection, since
`WebserviceException::getErrorMessage()->getValidationErrors()` has the same shape.

Testing
-------

Override the transport in the test container:

```yaml
# config/services_test.yaml
services:
    Polkurier\Webservice\Http\ClientFactoryInterface: '@Polkurier\Webservice\Testing\RecordingClient'
    Polkurier\Webservice\Testing\RecordingClient:
        public: true
```

Everything above the socket then runs for real. See [Testing](../testing.md) for the double itself.
