Symfony
=======

For Symfony 6.4, 7.4 and 8.x. There's no bundle to install: everything below is ordinary service
configuration.

Configuration
-------------

Read the credentials from the environment. In `.env`:

```dotenv
POLKURIER_LOGIN=12345
POLKURIER_TOKEN=
POLKURIER_SANDBOX=1
```

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
            $strictResponses: '%kernel.debug%'

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

The metadata is cached per class and the key doesn't include a version, so a deploy clears the
pool:

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

> With `methodsToLog` set on [`Config`](../../src/Config.php), full request and response bodies are logged. Secrets and
> personal data in them are hidden unless allowed. The exceptions in an error record's context are
> logged unaltered and can hold the full request.

Autowiring
----------

With that configuration in place, a service can ask for what it needs:

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

Using the facade
----------------

For one service instead of 16, [`PolkurierWebservice`](../../src/PolkurierWebservice.php) is registered and the per-service
aliases are left out:

```yaml
    Polkurier\Webservice\PolkurierWebservice:
        arguments:
            $config: '@Polkurier\Webservice\Api\ConfigInterface'
            $logger: '@monolog.logger.polkurier'
            $serializerProvider: '@Polkurier\Webservice\Serializer\SerializerProviderInterface'
```

Its other dependencies are optional and it builds them itself.

Moving the wiring into a bundle
-------------------------------

If you reuse the setup across projects, move it from `services.yaml` into a small bundle
extension:

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

If the credentials depend on the user, the shop or the request, implement
[`ConfigProviderInterface`](../../src/Api/ConfigProviderInterface.php) and alias it instead of [`ConfigProvider`](../../src/ConfigProvider.php):

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

The SDK asks the provider for its configuration on every call (see
[Configuration](../configuration.md#per-tenant-credentials)), so this works for as long as
`TenantContext` knows the current tenant. In a Messenger handler there is no current request, so
it usually doesn't. The client is then built for each message, from the tenant the message
carries:

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

The serializer is stateless and it's the expensive part, so you can share it and still build a
fresh client per message.

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
            // Unknown outcome. Let Messenger retry. The guard above is what makes
            // a second attempt safe once the number is stored.
            throw $e;
        }

        $order->polkurierNumber = $created->getOrderNumber();
        $this->repository->save($order);
    }
}
```

[`Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException`](https://github.com/symfony/symfony/blob/7.4/src/Symfony/Component/Messenger/Exception/UnrecoverableMessageHandlingException.php) stops Messenger from retrying a payload
that will never be accepted. Without it, a bad postcode uses up 3 retries and ends up in the
failure transport, where it looks like an outage. The guard at the top of
`PlaceShipmentHandler::__invoke()` makes it safe to retry after a [`ConnectionException`](../../src/Exception/ConnectionException.php). There's no idempotency key, so your own
record of the placed order is the only protection. A stricter version places the order with your
own external ids and looks it up by them with `get_orders` (see
[Errors](../errors.md#retrying-safely)).

A retry policy to match:

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

[`UtilsServiceInterface::heartbeat()`](../../src/Api/Service/UtilsServiceInterface.php) doesn't send credentials. It tells you whether the API is reachable, whether or not
the token is valid.

Validation errors in a form
---------------------------

[`ValidationException`](../../src/Exception/ValidationException.php) uses the wire field names, which won't match the fields in your form. One
function can map them:

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

[`WebserviceException::getErrorMessage()`](../../src/Exception/WebserviceException.php) followed by [`ErrorMessage::getValidationErrors()`](../../src/Http/Response/ErrorMessage.php) returns the same shape, so the same
function works when the API rejects the payload.

Testing
-------

Replace the transport in the test container:

```yaml
# config/services_test.yaml
services:
    Polkurier\Webservice\Http\ClientFactoryInterface: '@Polkurier\Webservice\Testing\RecordingClient'
    Polkurier\Webservice\Testing\RecordingClient:
        public: true
```

Everything except the network call then runs for real. The test double is described in
[Testing](../testing.md).
