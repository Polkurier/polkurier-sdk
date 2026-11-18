<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\ConfigProvider;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Http\Request\RequestFactory;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Service\AbstractService;
use Polkurier\Webservice\Testing\RecordingClient;
use Psr\Log\NullLogger;

/**
 * What the log context hides is decided by each value, not by the key it sits under.
 *
 * The keys the SDK's own log methods use were once a fixed list, so a method that named a key
 * anything else handed the logger an unprepared request — the token in clear — and no test
 * objected. These drive the preparation with keys the list never held.
 */
#[CoversClass(AbstractService::class)]
final class LogContextTest extends TestCase
{
    public function testARequestIsPreparedWhateverKeyItSitsUnder(): void
    {
        $context = $this->service()->prepare([
            'http_request' => new Request(
                'POST',
                '/',
                [],
                '{"apimethod":"get_addresses","authorization":{"login":"20534","token":"test-token"}}',
            ),
        ]);

        self::assertArrayHasKey('http_request', $context);
        self::assertStringContainsString('"token":"***"', (string)$context['http_request']->getBody());
        self::assertStringNotContainsString('test-token', (string)$context['http_request']->getBody());
    }

    /**
     * An object that cannot be prepared is left out, and an exception is named by its class, under
     * any key.
     *
     * @return void
     */
    public function testAnObjectUnderAnUnknownKeyIsLeftOutAndTheThrowableIsNamed(): void
    {
        $context = $this->service()->prepare([
            'whatever' => new AuthorizationMessage(token: 'test-token', login: '20534'),
            'failure' => new \RuntimeException('Jan Kowalski, jan@example.com'),
        ]);

        self::assertArrayNotHasKey('whatever', $context);
        self::assertSame(\RuntimeException::class, $context['failure']);
    }

    /**
     * What names the call rather than carrying its data stays as it is.
     *
     * @return void
     */
    public function testScalarsAndEnumsAreKept(): void
    {
        $context = $this->service()->prepare(['method' => ApiMethodEnum::GetAddresses, 'sandbox' => true]);

        self::assertSame(['method' => ApiMethodEnum::GetAddresses, 'sandbox' => true], $context);
    }

    private function service(): LogContextService
    {
        $configProvider = new ConfigProvider(new Config(
            authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
            platform: 'phpunit',
            platformVersion: '1.0.0',
        ));
        $serializer = new MessageSerializer(new SerializerProvider());

        return new LogContextService(
            new RequestFactory($serializer, $configProvider),
            new ResponseFactory($serializer),
            new NullLogger(),
            new RecordingClient(),
            $configProvider,
        );
    }
}

/**
 * Reaches the context preparation, which is protected because only a service calls it.
 */
final class LogContextService extends AbstractService
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function prepare(array $context): array
    {
        return $this->makeContextForLog($context, ApiMethodEnum::GetAddresses, null);
    }
}
