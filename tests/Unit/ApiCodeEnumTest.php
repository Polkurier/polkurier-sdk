<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;
use Polkurier\Webservice\Enum\Http\ApiStatusCodeEnum;
use Polkurier\Webservice\Exception\AuthorizationException;

/**
 * The enum backing values are the literal strings and numbers the API emits. A typo in one of them
 * silently misclassifies errors, which is how `TOKEN_IS_NO_ACTIVE` (with a trailing E the API never
 * sends) stopped expired tokens being recognised as authorization failures.
 */
#[CoversClass(ApiErrorCodeEnum::class)]
#[CoversClass(ApiStatusCodeEnum::class)]
#[CoversClass(AuthorizationException::class)]
final class ApiCodeEnumTest extends TestCase
{
    public function testRecognisesTheErrorCodeTheApiSendsForAnInactiveToken(): void
    {
        self::assertSame(ApiErrorCodeEnum::TokenNotActive, ApiErrorCodeEnum::from('TOKEN_IS_NO_ACTIV'));
        self::assertTrue(AuthorizationException::isAuthorizationErrorCode('TOKEN_IS_NO_ACTIV'));
    }

    public function testClassifiesEveryAuthorizationStatusCode(): void
    {
        foreach ([4, 5, 6, 7, 8, 21] as $status) {
            self::assertTrue(
                AuthorizationException::isAuthorizationErrorStatus($status),
                \sprintf('Status %d should be an authorization error.', $status)
            );
        }
    }

    public function testDoesNotClassifyUnrelatedStatusCodesAsAuthorizationErrors(): void
    {
        foreach ([2, 3, 9, 26, 429] as $status) {
            self::assertFalse(
                AuthorizationException::isAuthorizationErrorStatus($status),
                \sprintf('Status %d should not be an authorization error.', $status)
            );
        }
    }

    public function testStatusAndErrorCodeEnumsDescribeTheSameSetOfFailures(): void
    {
        $statusNames = \array_map(static fn(ApiStatusCodeEnum $c): string => $c->name, ApiStatusCodeEnum::cases());
        $errorNames = \array_map(static fn(ApiErrorCodeEnum $c): string => $c->name, ApiErrorCodeEnum::cases());

        \sort($statusNames);
        \sort($errorNames);

        self::assertSame($statusNames, $errorNames);
    }
}
