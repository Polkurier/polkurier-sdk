Slim, PHP-DI and any PSR-11 container
=====================================

The SDK has no container integration of its own and needs none: every collaborator is a plain
object with an explicit constructor. This page covers the containers that use PHP definitions
rather than a bundle or a provider, and finishes with no container at all.

PHP-DI
------

Used directly, or as Slim's container. One definitions file:

```php
<?php
// config/polkurier.php

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
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function DI\autowire;
use function DI\factory;
use function DI\get;

return [
    ConfigInterface::class => factory(static fn(): ConfigInterface => new Config(
        authorization: new AuthorizationMessage(
            token: (string)getenv('POLKURIER_TOKEN'),
            login: (string)getenv('POLKURIER_LOGIN'),
        ),
        platform: 'my-shop',
        platformVersion: '1.0.0',
        sandbox: (bool)getenv('POLKURIER_SANDBOX'),
    )),

    ConfigProviderInterface::class => autowire(ConfigProvider::class),

    // One serializer. This is the expensive object; never make it a transient.
    SerializerProviderInterface::class => factory(
        static fn(ContainerInterface $c): SerializerProviderInterface => new SerializerProvider(
            new SerializerConfig(metadataCache: $c->get(CacheItemPoolInterface::class)),
        ),
    ),

    ClientFactoryInterface::class   => autowire(ClientFactory::class),
    RequestFactoryInterface::class  => autowire(RequestFactory::class),
    ResponseFactoryInterface::class => autowire(ResponseFactory::class),

    ValidatorInterface::class => factory([DefaultValidatorRegistryFactory::class, 'create']),

    // The services. They all take the same six arguments, so autowire handles them
    // once the interfaces above are bound.
    OrdersServiceInterface::class  => autowire(OrdersService::class),
    CourierServiceInterface::class => autowire(CourierService::class),

    PolkurierWebservice::class => factory(
        static fn(ContainerInterface $c): PolkurierWebservice => new PolkurierWebservice(
            config: $c->get(ConfigInterface::class),
            logger: $c->get(LoggerInterface::class),
            serializerProvider: $c->get(SerializerProviderInterface::class),
            configProvider: $c->get(ConfigProviderInterface::class),
        ),
    ),
];
```

`autowire()` works on the services because every constructor parameter is an interface bound above,
with the exception of `LoggerInterface` — bind that to your application's logger, or PHP-DI will
try to instantiate the interface and fail.

Slim
----

Slim uses whatever PSR-11 container you give it, so the definitions above are the whole
integration:

```php
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config/polkurier.php');
$builder->enableCompilation(__DIR__ . '/../var/cache');   // production

AppFactory::setContainer($builder->build());
$app = AppFactory::create();
```

An action then asks for what it needs:

```php
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TrackAction
{
    public function __construct(private OrdersServiceInterface $orders) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $status = $this->orders->getStatus(new GetStatusMessage(orderNo: $args['number']));

        $response->getBody()->write(json_encode(['status' => $status->getStatus()]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

> With compilation enabled, PHP-DI freezes the definitions. Clear `var/cache` on deploy, and note
> that `factory()` closures cannot be compiled — either accept the small cost or move them into
> invokable factory classes.

A middleware that turns SDK exceptions into responses keeps the actions clean:

```php
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PolkurierErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responses) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            return $this->json(422, ['errors' => $e->getValidationErrors()]);
        } catch (WebserviceException $e) {
            return $this->json(502, [
                'error'  => $e->getErrorMessage()->getResponse(),
                'errors' => $e->getErrorMessage()->getValidationErrors() ?? [],
            ]);
        } catch (ConnectionException $e) {
            return $this->json(504, ['error' => 'The carrier API did not answer.']);
        }
    }

    // … $this->json() builds a PSR-7 response …
}
```

Laminas ServiceManager and Mezzio
---------------------------------

See [Laminas MVC and Mezzio](laminas-mezzio.md), which uses factory classes rather than closures.

No container at all
-------------------

A cron script, a small CLI tool, a legacy application. Construct the facade and be done:

```php
require __DIR__ . '/vendor/autoload.php';

use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

$client = new PolkurierWebservice(new Config(
    authorization: new AuthorizationMessage(
        token: (string)getenv('POLKURIER_TOKEN'),
        login: (string)getenv('POLKURIER_LOGIN'),
    ),
    platform: 'nightly-sync',
    platformVersion: '1.0.0',
));

foreach ($client->orders()->getOrders($filter)->getResult() as $order) {
    // …
}
```

Everything the facade needs is built internally on first use, and services are cached after that.

If you want one service and not the facade, wire it by hand. Every service takes the same six
arguments, in this order:

```php
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\OrdersService;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

$configProvider = new ConfigProvider($config);
$serializer     = new SerializerProvider();

$orders = new OrdersService(
    requestFactory:  new RequestFactory($serializer, $configProvider),
    responseFactory: new ResponseFactory($serializer),
    logger:          $logger,
    clientFactory:   new ClientFactory($configProvider),
    configProvider:  $configProvider,
    validator:       DefaultValidatorRegistryFactory::create(),
);
```

> **Pass the validator explicitly.** `AbstractService` defaults it to `NullValidator`, so a service
> constructed by hand validates nothing — the opposite of the facade's default. That default exists
> so a service built before validation existed keeps working; it is not what you want.

Long-running processes
----------------------

Swoole, RoadRunner, FrankenPHP, ReactPHP, and any worker that survives more than one job.

**Share the serializer, and give it a cache pool.** Reading attributes off a hundred and fifty
classes is the SDK's biggest start-up cost, and in a worker you pay it once rather than per request
— but only if the provider instance survives.

**Watch configuration lifetime.** `AbstractService` and `ClientFactory` each call `getConfig()`
once, in their constructors. A per-tenant `ConfigProviderInterface` therefore does *not* make a
long-lived service switch tenants. Build the client per job instead, sharing the serializer:

```php
$client = new PolkurierWebservice(
    config: $credentials->configFor($job->tenantId),
    serializerProvider: $sharedSerializer,     // safe to share: stateless
);
```

**Do not share a service across concurrent coroutines.** `AbstractService::$lastHttpResponse` is
per-call mutable state, kept so exceptions can report what arrived. In a shared-nothing process it
is unremarkable; under real concurrency, give each coroutine its own client.

**Static caches are per process, not per request.** `AttributeValidator`, `MessageFields`,
`OmitEmptyNormalizer` and `AbstractBuilder` each hold a static reflection cache keyed by class
name. They warm once and stay warm, which is the point — but it also means a filtered
`AttributeValidator` subclass must filter what `parent::rulesFor()` returns rather than replacing
the cached entry, or two differently configured validators will fight over it. See
[Validation](../validation.md#2-drop-one-rule-keep-the-rest).
