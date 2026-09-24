Laminas MVC and Mezzio
======================

Both use `laminas/laminas-servicemanager`, so the wiring is the same; only where the configuration
lives differs. Factory classes rather than closures, because ServiceManager can cache them and
because they are easier to test.

Configuration
-------------

Mezzio: `config/autoload/polkurier.global.php` for the safe parts, and
`polkurier.local.php` (git-ignored) for the credentials.

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
// config/autoload/polkurier.local.php   — not in version control

return [
    'polkurier' => [
        'login' => '12345',
        'token' => '…',
    ],
];
```

Laminas MVC puts the same arrays in `module/Application/config/module.config.php` and
`config/autoload/`, respectively.

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

One factory serves every service, since they all take the same six arguments:

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

Mezzio: `config/autoload/dependencies.global.php`. Laminas MVC: the `service_manager` key of
`module.config.php`.

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
            ClientFactoryInterface::class  => static fn($c) => new ClientFactory($c->get(ConfigProviderInterface::class)),
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

ServiceManager passes the requested name to the factory as its second argument, which is what lets
one factory class serve every service.

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

Its factory is one line of `ConfigAbstractFactory`, or a small class of your own.

Error handling middleware
-------------------------

Mezzio's pipeline is the right place to turn SDK exceptions into responses:

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

Pipe it in front of the routes that touch the SDK, not globally — a 502 is the right answer for a
shipping endpoint and the wrong one for everything else.

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

Controllers get theirs through `controllers.factories`, and a delegator is the cleanest way to add
cross-cutting behaviour to a service — logging every call, timing it — without wrapping it by hand:

```php
'delegators' => [
    OrdersServiceInterface::class => [TimingDelegator::class],
],
```

`laminas-cli` commands
----------------------

A reachability check, for deploy verification:

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

ServiceManager is not immutable by default, so this works in place. If yours is configured
otherwise, build a second container from the same config with the one key replaced.
