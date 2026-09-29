<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Testing\RecordingClient;

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
            'a free-form array expected, a number received' => [
                static fn(PolkurierWebservice $s) => $s->tickets()->getContactForm(new GetContactFormMessage(category: 'wspolpraca')),
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
}
