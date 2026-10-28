<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Testing\RecordingClient;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * How a service call fails, for each kind of failure.
 */
final class ErrorPathTest extends TestCase
{
    private RecordingClient $client;

    private PolkurierWebservice $sdk;

    protected function setUp(): void
    {
        $this->client = new RecordingClient();
        $this->sdk = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
            ),
            clientFactory: $this->client,
        );
    }

    /**
     * One call per request helper, each answered with a payload of the wrong shape.
     *
     * @return array<string, array{\Closure(PolkurierWebservice): mixed, string}>
     */
    public static function wrongShapes(): array
    {
        return [
            'an object expected, a string received' => [
                static fn(PolkurierWebservice $s) => $s->account()->getAccountInfo(),
                '"not an object"',
            ],
            'an object expected, nothing received' => [
                static fn(PolkurierWebservice $s) => $s->account()->getAccountInfo(),
                'null',
            ],
            'a list expected, a string received' => [
                static fn(PolkurierWebservice $s) => $s->bankAccounts()->getBankAccounts(),
                '"not a list"',
            ],
            'a string map expected, a string received' => [
                static fn(PolkurierWebservice $s) => $s->courier()->getCountries(new GetCountriesMessage(couriers: 'DHL')),
                '"not a map"',
            ],
            'a string map expected, nothing received' => [
                static fn(PolkurierWebservice $s) => $s->courier()->getCountries(new GetCountriesMessage(couriers: 'DHL')),
                'null',
            ],
            'an object expected, a number received' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->getContactForm(
                    new GetContactFormMessage(category: 'wspolpraca'),
                ),
                '42',
            ],
        ];
    }

    /**
     * @param \Closure(PolkurierWebservice): mixed $call
     */
    #[DataProvider('wrongShapes')]
    public function testAPayloadOfTheWrongShapeIsMalformed(\Closure $call, string $payload): void
    {
        $this->client->willAnswerWith('{"status":"success","response":' . $payload . '}');

        $this->expectException(MalformedResponseException::class);

        $call($this->sdk);
    }

    public function testATransportFailureIsAConnectionException(): void
    {
        $sdk = new PolkurierWebservice(
            config: new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
            ),
            clientFactory: new FailingClient(),
        );

        try {
            $sdk->utils()->heartbeat();
            self::fail('Expected a ConnectionException.');
        } catch (ConnectionException $e) {
            self::assertInstanceOf(ClientExceptionInterface::class, $e->getPrevious());
        }
    }

    public function testAnErrorEnvelopeIsAWebserviceException(): void
    {
        $this->client->willAnswerWith('{"status":"error","status_code":15,"error_code":"PARAMS_IS_EMPTY","response":"Brak parametru"}');

        try {
            $this->sdk->addresses()->getAddresses();
            self::fail('Expected a WebserviceException.');
        } catch (AuthorizationException) {
            self::fail('A missing parameter is not an authorization error.');
        } catch (WebserviceException $e) {
            self::assertSame(15, $e->getErrorMessage()->getStatusCode());
            self::assertSame('PARAMS_IS_EMPTY', $e->getErrorMessage()->getErrorCode());
        }
    }

    /**
     * A rejected contact form answers in capitals and carries the nonce for the next submission.
     */
    public function testARejectedContactFormCarriesTheNextNonce(): void
    {
        $this->client->willAnswerWith('{"status":"ERROR","status_code":26,"error_code":"VALIDATION_ERROR",'
            . '"response":"Należy wyrazić zgodę [consent]","validation_errors":{"consent":["Należy wyrazić zgodę"]},'
            . '"nonce":"n2"}');

        try {
            $this->sdk->addresses()->getAddresses();
            self::fail('Expected a WebserviceException.');
        } catch (WebserviceException $e) {
            self::assertSame('n2', $e->getErrorMessage()->getNonce());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizationErrors(): array
    {
        return [
            'by status code only' => ['{"status":"error","status_code":8,"response":"Token nieaktywny"}'],
            'by error code only' => ['{"status":"error","error_code":"TOKEN_IS_NO_ACTIV","response":"Token nieaktywny"}'],
        ];
    }

    #[DataProvider('authorizationErrors')]
    public function testAnAuthorizationErrorIsRecognisedFromEitherCode(string $envelope): void
    {
        $this->client->willAnswerWith($envelope);

        $this->expectException(AuthorizationException::class);

        $this->sdk->addresses()->getAddresses();
    }
}

final class FailingClient implements ClientInterface, ClientFactoryInterface
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
