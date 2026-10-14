<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Log\LoggedFileWriter;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Testing\RecordingClient;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
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

    public function testATransportFailureIsLoggedWithoutTheTokenByDefault(): void
    {
        try {
            $this->sdk(clientFactory: new RefusingClient())->addresses()->getAddresses();
            self::fail('The transport failure should have been raised.');
        } catch (ConnectionException) {
        }

        self::assertCount(1, $this->logger->records);
        self::assertStringNotContainsString('test-token', $this->logger->records[0]['message']);
        self::assertStringContainsString('"token":"***"', $this->logger->records[0]['message']);
        self::assertStringContainsString('"login":"20534"', $this->logger->records[0]['message']);
        self::assertArrayNotHasKey('payload', $this->logger->records[0]['context']);
        $request = $this->logger->records[0]['context']['request'] ?? null;
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertStringNotContainsString('test-token', (string)$request->getBody());
    }

    public function testAPinSentInALoggedCallIsHiddenByDefault(): void
    {
        $this->client->willAnswerWith('{"status":"success","response":true}');

        $this->sdk(methodsToLog: [ApiMethodEnum::ChangePhonePin])
            ->account()
            ->changePhonePin(new ChangePinMessage(pin: '1234'));

        $logged = \implode("\n", \array_column($this->logger->records, 'message'));
        self::assertStringNotContainsString('1234', $logged);
        self::assertStringNotContainsString('test-token', $logged);
        self::assertStringContainsString('"pin":"***"', $logged);
    }

    public function testAPinReceivedInALoggedCallIsHiddenByDefault(): void
    {
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        $account = $serializer->normalize(Sample::of(AccountInfoMessage::class), 'json');
        self::assertIsArray($account);
        $account['other_pin'] = '4321';
        $this->client->willAnswerWith((string)\json_encode(['status' => 'success', 'response' => $account]));

        $this->sdk(methodsToLog: [ApiMethodEnum::GetAccountInfo])->account()->getAccountInfo();

        $logged = \implode("\n", \array_column($this->logger->records, 'message'));
        self::assertStringNotContainsString('4321', $logged);
        foreach ($this->logger->records as $record) {
            self::assertArrayNotHasKey('parsed_response', $record['context']);
        }
    }

    public function testSecretsAreLoggedAsTheyAreWhenAllowed(): void
    {
        try {
            $this->sdk(logSensitiveData: true, clientFactory: new RefusingClient())->addresses()->getAddresses();
        } catch (ConnectionException) {
        }

        self::assertStringContainsString('"token":"test-token"', $this->logger->records[0]['message']);
        self::assertArrayNotHasKey('payload', $this->logger->records[0]['context'], 'personal data is still hidden');
    }

    public function testThePayloadIsInTheContextWhenSecretsAndPersonalDataAreBothAllowed(): void
    {
        try {
            $this->sdk(logSensitiveData: true, logPersonalData: true, clientFactory: new RefusingClient())
                ->addresses()->getAddresses();
        } catch (ConnectionException) {
        }

        self::assertArrayHasKey('payload', $this->logger->records[0]['context']);
    }

    public function testPersonalDataIsHiddenByDefaultAndLoggedWhenAllowed(): void
    {
        $answer = '{"status":"success","response":[{"id":"7","number":"61109010140000071219812874","name":"Firmowe","default":true}]}';

        $this->client->willAnswerWith($answer);
        $this->sdk(methodsToLog: [ApiMethodEnum::GetBankAccounts])->bankAccounts()->getBankAccounts();
        $hidden = \implode("\n", \array_column($this->logger->records, 'message'));

        $this->logger->records = [];
        $this->client->willAnswerWith($answer);
        $this->sdk(methodsToLog: [ApiMethodEnum::GetBankAccounts], logPersonalData: true)
            ->bankAccounts()
            ->getBankAccounts();
        $shown = \implode("\n", \array_column($this->logger->records, 'message'));

        self::assertStringNotContainsString('61109010140000071219812874', $hidden);
        self::assertStringContainsString('"number":"***"', $hidden);
        self::assertStringContainsString('"login":"20534"', $hidden);
        self::assertStringContainsString('61109010140000071219812874', $shown);
    }

    public function testAFileIsLeftOutOfTheLogByDefault(): void
    {
        $pdf = \base64_encode("%PDF-1.4\nlabel");
        $this->client->willAnswerWith('{"status":"success","response":{"file":"' . $pdf . '"}}');

        $this->sdk(methodsToLog: [ApiMethodEnum::GetLabel])
            ->orders()
            ->getLabel(new GetDocumentMessage(orderNo: ['12345-1']));

        $logged = \implode("\n", \array_column($this->logger->records, 'message'));
        self::assertStringNotContainsString($pdf, $logged);
        self::assertStringContainsString('[file left out of the log: 14 bytes]', $logged);
    }

    public function testAFileIsSavedOnceAndPointedToWhenAllowed(): void
    {
        $directory = \sys_get_temp_dir() . '/polkurier-sdk-test-' . \bin2hex(\random_bytes(4));
        $pdf = \base64_encode("%PDF-1.4\nlabel");
        $this->client->willAnswerWith('{"status":"success","response":{"file":"' . $pdf . '"}}');

        try {
            $this->sdk(methodsToLog: [ApiMethodEnum::GetLabel], logFiles: true, logFilesDirectory: $directory)
                ->orders()->getLabel(new GetDocumentMessage(orderNo: ['12345-1']));

            $saved = \glob($directory . '/*') ?: [];
            self::assertCount(1, $saved, 'the body is in both the message and the context, but the file is saved once');
            self::assertMatchesRegularExpression(LoggedFileWriter::FILE_NAME_PATTERN, \basename($saved[0]));
            self::assertStringContainsString('-get_label-', \basename($saved[0]));
            self::assertStringEndsWith('-file.pdf', $saved[0]);
            self::assertSame("%PDF-1.4\nlabel", \file_get_contents($saved[0]));

            $logged = \implode("\n", \array_column($this->logger->records, 'message'));
            self::assertStringNotContainsString($pdf, $logged);
            self::assertStringContainsString('[file saved to ' . $saved[0] . ': 14 bytes]', $logged);
        } finally {
            \array_map('unlink', \glob($directory . '/*') ?: []);
            @\rmdir($directory);
        }
    }

    /**
     * @param ApiMethodEnum[] $methodsToLog
     */
    private function sdk(
        bool $loggingEnabled = true,
        array $methodsToLog = [],
        bool $logSensitiveData = false,
        ?ClientFactoryInterface $clientFactory = null,
        bool $logPersonalData = false,
        bool $logFiles = false,
        ?string $logFilesDirectory = null,
    ): PolkurierWebservice {
        return new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
                loggingEnabled: $loggingEnabled,
                methodsToLog: $methodsToLog,
                logSensitiveData: $logSensitiveData,
                logPersonalData: $logPersonalData,
                logFiles: $logFiles,
                logFilesDirectory: $logFilesDirectory,
            ),
            logger: $this->logger,
            clientFactory: $clientFactory ?? $this->client,
        );
    }
}

/**
 * A PSR-3 logger that, like most non-Monolog loggers, only accepts the standard level strings.
 */
final class SpyLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $records = [];

    /**
     * Untyped $message, so the signature fits every psr/log major version.
     *
     * @param string|\Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!\is_string($level)) {
            throw new \Psr\Log\InvalidArgumentException('Level must be a PSR-3 level string.');
        }

        $this->records[] = ['level' => $level, 'message' => (string)$message, 'context' => $context];
    }
}

/**
 * A client whose every request fails at the transport level.
 */
final class RefusingClient implements ClientInterface, ClientFactoryInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new class ('Connection refused') extends \RuntimeException implements ClientExceptionInterface {};
    }

    public function create(): ClientInterface
    {
        return $this;
    }
}
