<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Testing\RecordingClient;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * What the SDK hands to the PSR-3 logger, and when.
 */
final class LoggingTest extends TestCase
{
    private RecordingClient $client;

    private SpyLogger $logger;

    protected function setUp(): void
    {
        $this->client = new RecordingClient();
        $this->logger = new SpyLogger();
    }

    public function testAnErrorResponseIsLoggedWithAPsr3LevelString(): void
    {
        $this->client->willAnswerWith('{"status":"error","response":"Nieznany błąd"}');

        try {
            $this->sdk()->addresses()->getAddresses();
            self::fail('The error response should have been raised.');
        } catch (WebserviceException) {
        }

        self::assertCount(1, $this->logger->records);
        self::assertSame(LogLevel::ERROR, $this->logger->records[0]['level']);
    }

    public function testAResponseThatCannotBeReadIsLogged(): void
    {
        $this->client->willAnswerWith('<html>Bad gateway</html>');

        try {
            $this->sdk()->addresses()->getAddresses();
            self::fail('The malformed response should have been raised.');
        } catch (MalformedResponseException) {
        }

        self::assertCount(1, $this->logger->records);
        self::assertSame(LogLevel::ERROR, $this->logger->records[0]['level']);
    }

    public function testAnUnknownEnumValueIsLoggedAsAWarningAndTheCallSucceeds(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[{"id":"1","type":"SOMETHING_NEW","name":"Magazyn","person":"Jan Kowalski","street":"Długa","city":"Poznań","postcode":"61-001","email":"jan@example.com","phone":"500600700","housenumber":"12"}]}');

        $addresses = $this->sdk()->addresses()->getAddresses();

        self::assertCount(1, $addresses);
        self::assertNull($addresses[0]->getType());
        self::assertCount(1, $this->logger->records);
        self::assertSame(LogLevel::WARNING, $this->logger->records[0]['level']);
        self::assertStringContainsString('"SOMETHING_NEW"', $this->logger->records[0]['message']);
        self::assertStringContainsString('[0].type', $this->logger->records[0]['message']);
    }

    public function testAnUnknownEnumValueIsNotLoggedWhenLoggingIsOff(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[{"id":"1","type":"SOMETHING_NEW","name":"Magazyn","person":"Jan Kowalski","street":"Długa","city":"Poznań","postcode":"61-001","email":"jan@example.com","phone":"500600700","housenumber":"12"}]}');

        $this->sdk(loggingEnabled: false)->addresses()->getAddresses();

        self::assertSame([], $this->logger->records);
    }

    public function testASuccessIsNotLoggedUnlessTheMethodIsListed(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk()->addresses()->getAddresses();

        self::assertSame([], $this->logger->records);
    }

    public function testAListedMethodIsLoggedAtInfoLevel(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":[]}');

        $this->sdk(methodsToLog: [ApiMethodEnum::GetAddresses])->addresses()->getAddresses();

        self::assertNotSame([], $this->logger->records);
        foreach ($this->logger->records as $record) {
            self::assertSame(LogLevel::INFO, $record['level']);
        }
    }

    public function testNothingIsLoggedWhenLoggingIsOff(): void
    {
        $this->client->willAnswerWith('{"status":"error","response":"Nieznany błąd"}');

        try {
            $this->sdk(loggingEnabled: false)->addresses()->getAddresses();
        } catch (WebserviceException) {
        }

        self::assertSame([], $this->logger->records);
    }

    /**
     * @param ApiMethodEnum[] $methodsToLog
     */
    private function sdk(bool $loggingEnabled = true, array $methodsToLog = []): PolkurierWebservice
    {
        return new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
                loggingEnabled: $loggingEnabled,
                methodsToLog: $methodsToLog,
            ),
            logger: $this->logger,
            clientFactory: $this->client,
        );
    }
}

/**
 * A PSR-3 logger that, like most non-Monolog loggers, only accepts the standard level strings.
 */
final class SpyLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string}>
     */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (!\is_string($level)) {
            throw new \Psr\Log\InvalidArgumentException('Level must be a PSR-3 level string.');
        }

        $this->records[] = ['level' => $level, 'message' => (string)$message];
    }
}
