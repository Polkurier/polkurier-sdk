Laminas MVC and Mezzio
======================

Both are built on `laminas/laminas-servicemanager`, so the wiring is the same. Only the location
of the configuration files differs. The examples use factory classes for the main services:
ServiceManager can cache them, and they are easier to test than closures.

Configuration
-------------

In Mezzio, the non-secret settings go in `config/autoload/polkurier.global.php` and the
credentials in `polkurier.local.php`, which is git-ignored.

```php
<?php
// config/autoload/polkurier.global.php

return [
    'polkurier' => [
        'platform'         => 'my-shop',
        'platform_version' => '1.0.0',
        'sandbox'          => true,
        'timeout'          => 30,
        'log_methods'      => [],
    ],
];
```

```php
<?php
// config/autoload/polkurier.local.php   (not in version control)

return [
    'polkurier' => [
        'login' => '12345',
        'token' => '…',
    ],
];
```

In Laminas MVC the same arrays go in `module/Application/config/module.config.php` and
`config/autoload/`.

Factories
---------

```php
<?php
// src/App/Polkurier/ConfigFactory.php

namespace App\Polkurier;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Psr\Container\ContainerInterface;

final class ConfigFactory
{
    public function __invoke(ContainerInterface $container): ConfigInterface
    {
        $config = $container->get('config')['polkurier'];

        return new Config(
            authorization: new AuthorizationMessage(
                token: $config['token'],
                login: $config['login'],
            ),
            platform: $config['platform'],
            platformVersion: $config['platform_version'],
            apiTimeout: $config['timeout'],
            sandbox: $config['sandbox'],
            methodsToLog: $config['log_methods'],
        );
    }
}
```

```php
<?php
// src/App/Polkurier/SerializerProviderFactory.php

namespace App\Polkurier;

use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

final class SerializerProviderFactory
{
    public function __invoke(ContainerInterface $container): SerializerProviderInterface
    {
        return new SerializerProvider(new SerializerConfig(
            metadataCache: $container->has(CacheItemPoolInterface::class)
                ? $container->get(CacheItemPoolInterface::class)
                : null,
        ));
    }
}
```

All the services take the same arguments, so one factory can build any of them:

```php
<?php
// src/App/Polkurier/ServiceFactory.php

namespace App\Polkurier;

use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Builds any of the SDK's services. Registered once per service, keyed by its interface.
 */
final class ServiceFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName): object
    {
        $concrete = ServiceMap::CONCRETE[$requestedName] ?? $requestedName;

        return new $concrete(
            $container->get(RequestFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(ClientFactoryInterface::class),
            $container->get(ConfigProviderInterface::class),
            $container->get(ValidatorInterface::class),
        );
    }
}
```

```php
<?php
// src/App/Polkurier/ServiceMap.php

namespace App\Polkurier;

use Polkurier\Webservice\Api\Service as Contract;
use Polkurier\Webservice\Service as Concrete;

final class ServiceMap
{
    /** @var array<class-string, class-string> */
    public const array CONCRETE = [
        Contract\OrdersServiceInterface::class    => Concrete\OrdersService::class,
        Contract\CourierServiceInterface::class   => Concrete\CourierService::class,
        Contract\AddressesServiceInterface::class => Concrete\AddressesService::class,
        // … add the ones you use.
    ];
}
```

Dependency configuration
------------------------

In Mezzio this goes in `config/autoload/dependencies.global.php`. In Laminas MVC, it goes under the
`service_manager` key of `module.config.php`.

```php
<?php

use App\Polkurier\ConfigFactory;
use App\Polkurier\SerializerProviderFactory;
use App\Polkurier\ServiceFactory;
use App\Polkurier\ServiceMap;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Http\ClientFactory;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

return [
    'dependencies' => [
        'factories' => [
            ConfigInterface::class             => ConfigFactory::class,
            SerializerProviderInterface::class => SerializerProviderFactory::class,

            ConfigProviderInterface::class => static fn($c) => new ConfigProvider($c->get(ConfigInterface::class)),
            ClientFactoryInterface::class  => static fn($c) => new ClientFactory(
                $c->get(ConfigProviderInterface::class),
            ),
            RequestFactoryInterface::class => static fn($c) => new RequestFactory(
                $c->get(SerializerProviderInterface::class),
                $c->get(ConfigProviderInterface::class),
            ),
            ResponseFactoryInterface::class => static fn($c) => new ResponseFactory(
                $c->get(SerializerProviderInterface::class),
            ),
            ValidatorInterface::class => static fn() => DefaultValidatorRegistryFactory::create(),
        ]
        // Every SDK service, through the one factory.
        + array_fill_keys(array_keys(ServiceMap::CONCRETE), ServiceFactory::class),
    ],
];
```

ServiceManager passes the requested service name to the factory as the second argument. That's
how a single factory class can build every service.

Using it
--------

A Mezzio handler:

```php
namespace App\Handler;

use Polkurier\Webservice\Api\Service\OrdersServiceInterface;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class TrackHandler implements RequestHandlerInterface
{
    public function __construct(private OrdersServiceInterface $orders) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $status = $this->orders->getStatus(
            new GetStatusMessage(orderNo: $request->getAttribute('number')),
        );

        return new JsonResponse(['status' => $status->getStatus()]);
    }
}
```

Its factory can be a one-line [`Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory`](https://docs.laminas.dev/laminas-servicemanager/v4/config-abstract-factory/) entry or a small class.

Error handling middleware
-------------------------

In Mezzio, the middleware pipeline can convert SDK exceptions into responses:

```php
namespace App\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PolkurierErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            return new JsonResponse(['errors' => $e->getValidationErrors()], 422);
        } catch (WebserviceException $e) {
            $error = $e->getErrorMessage();

            return new JsonResponse([
                'error'  => $error->getResponse(),
                'code'   => $error->getErrorCode(),
                'errors' => $error->getValidationErrors() ?? [],
            ], 502);
        } catch (ConnectionException) {
            return new JsonResponse(['error' => 'The carrier API did not answer.'], 504);
        }
    }
}
```

It is piped only in front of the routes that call the SDK. A 502 suits a shipping endpoint, not
every other route.

Laminas MVC specifics
---------------------

The same factories go in `module.config.php`:

```php
return [
    'service_manager' => [
        'factories' => [ /* as above */ ],
    ],
];
```

Controllers get their dependencies through `controllers.factories`. A delegator adds behaviour
around a service (e.g. logging or timing every call) without wrapping it by hand:

```php
'delegators' => [
    OrdersServiceInterface::class => [TimingDelegator::class],
],
```

`laminas-cli` commands
----------------------

A reachability check you can run after a deploy:

```php
namespace App\Command;

use Polkurier\Webservice\Api\Service\UtilsServiceInterface;
use Polkurier\Webservice\Exception\Exception as PolkurierException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PingCommand extends Command
{
    public function __construct(private readonly UtilsServiceInterface $utils)
    {
        parent::__construct('polkurier:ping');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('API time: ' . $this->utils->heartbeat()->getTime()->format('c'));

            return self::SUCCESS;
        } catch (PolkurierException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }
}
```

Testing
-------

Override one entry in the test container:

```php
$container->setService(ClientFactoryInterface::class, new RecordingClient($cannedBody));
```

ServiceManager accepts this as long as nothing has fetched that entry yet, or always with
`allow_override` on. Otherwise, a second container built from the same config with that one key
replaced does the same.
