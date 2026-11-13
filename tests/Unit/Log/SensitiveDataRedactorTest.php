<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Log\SensitiveDataRedactor;

#[CoversClass(SensitiveDataRedactor::class)]
final class SensitiveDataRedactorTest extends TestCase
{
    public function testHidesSecretsAtAnyDepthAndKeepsTheRest(): void
    {
        $json = (string)\json_encode([
            'apimethod' => 'save_bank_account',
            'authorization' => ['login' => '12345', 'token' => 'secret-token'],
            'data' => [
                'account_number' => '61109010140000071219812874',
                'access_pin' => '1234',
                'two_factor_code' => '987654',
                'nested' => [['password' => 'hunter2', 'pin_locks' => ['PIN_SETTINGS' => true]]],
            ],
        ]);

        $redacted = \json_decode((new SensitiveDataRedactor())->redactJson($json), true);

        self::assertSame(['login' => '12345', 'token' => '***'], $redacted['authorization']);
        self::assertSame([
            'account_number' => '61109010140000071219812874',
            'access_pin' => '***',
            'two_factor_code' => '***',
            'nested' => [['password' => '***', 'pin_locks' => ['PIN_SETTINGS' => true]]],
        ], $redacted['data']);
    }

    /**
     * The kinds of secret a logged body hides are promised, so an integration can stop hiding
     * them itself. The set only grows, which is why this names each kind rather than the list.
     *
     * @see docs/architecture.md
     *
     * @return void
     */
    public function testHidesEveryKindOfSecretTheContractNames(): void
    {
        $sent = [
            'token' => 'api-token',
            'password' => 'hunter2',
            'previous_password' => 'hunter1',
            'pin' => '1234',
            'security_pin' => '5678',
            'access_pin' => '9012',
            'two_factor_code' => '987654',
            'two_factor_token' => 'tf-token',
            'code' => '112233',
            'access_token' => 'oauth-token',
        ];

        $redacted = \json_decode(
            (new SensitiveDataRedactor())->redactJson((string)\json_encode($sent)),
            true,
        );

        // Built from what went in, not from what came back: an expectation taken from the output
        // would also pass for a redactor that dropped a secret, or for an empty body.
        self::assertSame(\array_fill_keys(\array_keys($sent), '***'), $redacted);
    }

    public function testLeavesEmptySecretsAndNonJsonBodiesAlone(): void
    {
        $redactor = new SensitiveDataRedactor();

        self::assertSame('{"token":"","pin":null}', $redactor->redactJson('{"token":"","pin":null}'));
        self::assertSame('<html>Bad gateway</html>', $redactor->redactJson('<html>Bad gateway</html>'));
    }

    public function testACopyOfTheMessageHasTheSecretHiddenAndTheOriginalIsUntouched(): void
    {
        $request = new Request(
            'POST',
            'https://api.example.test',
            [],
            '{"authorization":{"login":"1","token":"t0k3n"}}',
        );

        $copy = (new SensitiveDataRedactor())->message($request);

        self::assertSame('{"authorization":{"login":"1","token":"***"}}', (string)$copy->getBody());
        self::assertSame('{"authorization":{"login":"1","token":"t0k3n"}}', (string)$request->getBody());
    }
}
