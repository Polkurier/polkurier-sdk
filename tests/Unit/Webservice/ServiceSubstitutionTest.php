<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Request\Order\CancelOrderMessage;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\OrdersService;
use Polkurier\Webservice\Service\UtilsService;
use Polkurier\Webservice\Testing\RecordingClient;
use Polkurier\Webservice\Validator\NullValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * A service the SDK did not build does not have to look like one it did.
 *
 * The facade builds its own services by calling the constructor they all share, which is fine for
 * them and was wrong as a general rule: a service needing one more collaborator could not be used
 * at all. It takes ready-made ones now, so its own convention stops being everyone's.
 */
#[CoversClass(PolkurierWebservice::class)]
final class ServiceSubstitutionTest extends TestCase
{
    public function testAServiceWithItsOwnConstructorCanBeHandedIn(): void
    {
        $client = new RecordingClient();
        $mine = new OrdersServiceWithExtra(...[
            ...self::collaborators($client),
            'extra' => 'a dependency of its own',
        ]);

        $sdk = self::sdk($client, [OrdersService::class => $mine]);

        self::assertSame($mine, $sdk->orders());
        self::assertSame('a dependency of its own', $sdk->orders()->extra);
    }

    public function testTheOthersAreStillBuiltOnFirstUse(): void
    {
        $client = new RecordingClient();
        $sdk = self::sdk($client, [OrdersService::class => new UtilsService(...self::collaborators($client))]);

        self::assertInstanceOf(UtilsService::class, $sdk->utils());
        self::assertSame($sdk->utils(), $sdk->utils(), 'still built once and kept');
    }

    public function testAServiceBuiltByHandChecksPayloadsAsTheFacadeDoes(): void
    {
        $client = new RecordingClient();
        $collaborators = self::collaborators($client);
        unset($collaborators['validator']);
        $orders = new OrdersService(...$collaborators);

        $this->expectException(ValidationException::class);
        $orders->cancelOrder(new CancelOrderMessage('not an order number'));
    }

    /**
     * The collaborators the SDK's own services take, wired as the facade wires them.
     *
     * @param ClientFactoryInterface $clientFactory
     *
     * @return array{
     *     requestFactory: RequestFactoryInterface,
     *     responseFactory: ResponseFactoryInterface,
     *     logger: LoggerInterface,
     *     clientFactory: ClientFactoryInterface,
     *     configProvider: ConfigProviderInterface,
     *     validator: ValidatorInterface
     * }
     */
    private static function collaborators(ClientFactoryInterface $clientFactory): array
    {
        $configProvider = new ConfigProvider(self::config());
        $serializer = new MessageSerializer(new SerializerProvider());

        return [
            'requestFactory' => new RequestFactory($serializer, $configProvider),
            'responseFactory' => new ResponseFactory($serializer),
            'logger' => new NullLogger(),
            'clientFactory' => $clientFactory,
            'configProvider' => $configProvider,
            'validator' => new NullValidator(),
        ];
    }

    /**
     * @param ClientFactoryInterface $clientFactory
     * @param array<class-string, object> $services
     *
     * @return PolkurierWebservice
     */
    private static function sdk(ClientFactoryInterface $clientFactory, array $services): PolkurierWebservice
    {
        return new PolkurierWebservice(
            config: self::config(),
            clientFactory: $clientFactory,
            services: $services,
        );
    }

    /**
     * @return Config
     */
    private static function config(): Config
    {
        return new Config(
            authorization: new AuthorizationMessage(token: 'test-token', login: '12345'),
            platform: 'phpunit',
            platformVersion: '1.0.0',
        );
    }
}

/**
 * An orders service that needs something the SDK does not provide.
 */
final class OrdersServiceWithExtra extends OrdersService
{
    /**
     * @param RequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param LoggerInterface $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ConfigProviderInterface $configProvider
     * @param ValidatorInterface $validator
     * @param string $extra
     */
    public function __construct(
        RequestFactoryInterface $requestFactory,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger,
        ClientFactoryInterface $clientFactory,
        ConfigProviderInterface $configProvider,
        ValidatorInterface $validator,
        public readonly string $extra,
    ) {
        parent::__construct(
            $requestFactory,
            $responseFactory,
            $logger,
            $clientFactory,
            $configProvider,
            $validator,
        );
    }
}
