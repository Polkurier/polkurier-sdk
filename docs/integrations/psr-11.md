Slim, PHP-DI and any PSR-11 container
=====================================

The SDK doesn't need a container integration. Each class is a plain object with an explicit
constructor.

PHP-DI
------

This works whether you use PHP-DI on its own or as Slim's container. You need one definitions
file:

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

    // One serializer. This is the expensive object, so it is shared.
    SerializerProviderInterface::class => factory(
        static fn(ContainerInterface $c): SerializerProviderInterface => new SerializerProvider(
            new SerializerConfig(metadataCache: $c->get(CacheItemPoolInterface::class)),
        ),
    ),

    ClientFactoryInterface::class   => autowire(ClientFactory::class),
    RequestFactoryInterface::class  => autowire(RequestFactory::class),
    ResponseFactoryInterface::class => autowire(ResponseFactory::class),

    ValidatorInterface::class => factory([DefaultValidatorRegistryFactory::class, 'create']),

    // The services. They all take the same 6 arguments, so autowire handles them
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

PHP-DI's [`DI\autowire()`](https://php-di.org/doc/php-definitions.html) can build the services because each constructor parameter is an interface bound
above. The exception is [`Psr\Log\LoggerInterface`](https://www.php-fig.org/psr/psr-3/): unless it is bound to the application's logger, PHP-DI
tries to instantiate the interface and fails.

Slim
----

Slim works with any PSR-11 container, so the definitions above are all you need:

```php
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config/polkurier.php');
$builder->enableCompilation(__DIR__ . '/../var/cache');   // production

AppFactory::setContainer($builder->build());
$app = AppFactory::create();
```

An action can then ask for its dependencies:

```php
use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class TrackAction
{
    public function __construct(private OrdersServiceInterface $orders) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $status = $this->orders->getStatus(new GetStatusMessage(orderNo: $args['number']));

        $response->getBody()->write(json_encode(['status' => $status->getStatus()]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

> With compilation enabled, PHP-DI freezes the definitions, so a deploy clears `var/cache`.
> [`DI\factory()`](https://php-di.org/doc/php-definitions.html) closures can't be compiled, while invokable factory classes can.

A middleware can convert SDK exceptions into responses, which keeps error handling out of the
actions:

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

See [Laminas MVC and Mezzio](laminas-mezzio.md). That page uses factory classes for the main
services.

No container at all
-------------------

For a cron script, a small CLI tool or a legacy application, the facade can be constructed
directly:

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

The facade builds what it needs on first use and caches the services after that.

If you only want one service, you can wire it by hand. Every service takes the same 6 arguments,
in this order:

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

> [`AbstractService`](../../src/Service/AbstractService.php) defaults the validator to [`NullValidator`](../../src/Validator/NullValidator.php), so a service constructed by hand
> doesn't validate anything, while the facade validates by default.

Long-running processes
----------------------

This covers Swoole, RoadRunner, FrankenPHP, ReactPHP and any worker that handles more than one job.

**The serializer is the start-up cost.** Reading the attributes of about 150 classes is the SDK's
biggest start-up cost. In a worker it is paid once, as long as the same provider instance is kept
between requests, and a cache pool keeps it across restarts.

**The tenant has to be known per job.** The SDK calls [`ConfigProviderInterface::getConfig()`](../../src/Api/ConfigProviderInterface.php) on
every request, so a per-tenant provider works in a long-lived worker, as long as it can tell which
tenant the current job belongs to. Where it can't, the client is built per job with a shared
serializer:

```php
$client = new PolkurierWebservice(
    config: $credentials->configFor($job->tenantId),
    serializerProvider: $sharedSerializer,     // safe to share: stateless
);
```

**Services hold per-call state.** [`AbstractService::$lastHttpResponse`](../../src/Service/AbstractService.php) is mutable state set on each
call, so that exceptions can report the response that came back. In a shared-nothing process that's
harmless. With real concurrency, coroutines that share a client overwrite it for each other.

**Static caches last for the whole process.** [`AttributeValidator`](../../src/Validator/AttributeValidator.php), [`MessageFields`](../../src/Validator/MessageFields.php),
[`OmitEmptyNormalizer`](../../src/Serializer/OmitEmptyNormalizer.php) and [`AbstractBuilder`](../../src/Builder/AbstractBuilder.php) each keep a static reflection cache keyed by class
name. Once filled, they stay filled. As a result, an [`AttributeValidator`](../../src/Validator/AttributeValidator.php) subclass that filters
rules must filter what [`AttributeValidator::rulesFor()`](../../src/Validator/AttributeValidator.php) returns. If it replaces the cached entry, two
validators with different settings will overwrite each other's rules. See
[Validation](../validation.md#2-drop-one-rule-keep-the-rest).
