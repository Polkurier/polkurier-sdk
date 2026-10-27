<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Request\Common\TokenMessage;
use Polkurier\Webservice\Request\TwoFactorToken\ChangeTransportMethodMessage;
use Polkurier\Webservice\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Response\TwoFactorToken;

/**
 * Registering a token sends a code to the account's phone or e-mail, so those tests run only with
 * POLKURIER_API_SEND_CODES=1. The others send requests the API refuses or rejects.
 */
#[Group('integration')]
#[CoversNothing]
final class TwoFactorTokenTest extends IntegrationTestCase
{
    private const string UNKNOWN_TOKEN = '00000000-0000-4000-8000-000000000000';

    public function testATokenIsRegistered(): TwoFactorToken
    {
        self::requireSandbox();
        if (\getenv('POLKURIER_API_SEND_CODES') !== '1') {
            self::markTestSkipped('Sends a code; set POLKURIER_API_SEND_CODES=1 to run it.');
        }

        $token = $this->sdk->twoFactorToken()->twoFactorTokenRegister(
            new RegisterMessage(namespace: TwoFactorNamespaceEnum::ChangeDefaultPhoneNumber),
        );

        self::assertNotSame('', $token->getId());

        return $token;
    }

    #[Depends('testATokenIsRegistered')]
    public function testTheCodeIsSentAgain(TwoFactorToken $token): void
    {
        $resent = $this->sdk->twoFactorToken()->twoFactorTokenResend(new TokenMessage(token: $token->getId()));

        self::assertSame($token->getId(), $resent->getId());
    }

    #[Depends('testATokenIsRegistered')]
    public function testTheChannelIsSwitched(TwoFactorToken $token): void
    {
        $other = \array_values(\array_filter(
            $token->getAvailableMethods(),
            static fn($method): bool => $method->getId() !== $token->getTransportMethod()?->getId(),
        ));
        if ($other === []) {
            self::markTestSkipped('The account has one channel only.');
        }

        $switched = $this->sdk->twoFactorToken()->twoFactorTokenChangeTransportMethod(
            new ChangeTransportMethodMessage(token: $token->getId(), method: $other[0]->getId()),
        );

        self::assertSame($other[0]->getId(), $switched->getTransportMethod()?->getId());
    }

    public function testAWrongCodeForAnUnknownTokenIsInvalid(): void
    {
        $validity = $this->sdk->twoFactorToken()->twoFactorTokenVerify(
            new VerifyMessage(token: self::UNKNOWN_TOKEN, code: '123456'),
        );

        self::assertFalse($validity->isValid());
    }

    public function testResendingNeedsAToken(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->twoFactorToken()->twoFactorTokenResend(
                new TokenMessage(token: ''),
            ),
            'PARAMS_IS_EMPTY',
        );
    }

    public function testChangingTheChannelNeedsAMethod(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->twoFactorToken()->twoFactorTokenChangeTransportMethod(
                new ChangeTransportMethodMessage(token: self::UNKNOWN_TOKEN, method: ''),
            ),
            'PARAMS_IS_EMPTY',
        );
    }
}
