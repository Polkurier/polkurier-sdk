<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;
use Polkurier\Webservice\Exception\AuthorizationException;

/**
 * The enum backing values are the literal strings and numbers the API emits. A typo in one of them
 * silently misclassifies errors, e.g. `TOKEN_IS_NO_ACTIVE` with a trailing E the API never sends
 * would stop expired tokens being recognised as authorization failures.
 */
#[CoversClass(ApiErrorCodeEnum::class)]
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
        foreach (self::rejectingCases() as $case) {
            self::assertTrue(
                AuthorizationException::isAuthorizationErrorStatus($case->getStatusCode()),
                \sprintf('Status %d should be an authorization error.', $case->getStatusCode())
            );
        }
    }

    /**
     * {@see ApiErrorCodeEnum::rejectsCredentials()} decides which codes mean the credentials were
     * refused; {@see AuthorizationException::ERROR_CODES} repeats them for a caller that wants
     * strings. A case added to one and not the other would stop being reported as an
     * authorization failure.
     *
     * @return void
     */
    public function testTheListedErrorCodesAreTheOnesTheEnumRejectsCredentialsFor(): void
    {
        $fromEnum = \array_map(static fn(ApiErrorCodeEnum $case): string => $case->value, self::rejectingCases());

        self::assertEqualsCanonicalizing($fromEnum, AuthorizationException::ERROR_CODES);
    }

    /**
     * @return list<ApiErrorCodeEnum>
     */
    private static function rejectingCases(): array
    {
        return \array_values(\array_filter(
            ApiErrorCodeEnum::cases(),
            static fn(ApiErrorCodeEnum $case): bool => $case->rejectsCredentials(),
        ));
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

    public function testEveryCaseMapsBackFromItsStatusCodeUnlessTheCodeIsShared(): void
    {
        $codes = \array_map(static fn(ApiErrorCodeEnum $c): int => $c->getStatusCode(), ApiErrorCodeEnum::cases());
        $counts = \array_count_values($codes);

        foreach (ApiErrorCodeEnum::cases() as $case) {
            $expected = $counts[$case->getStatusCode()] === 1 ? $case : null;
            self::assertSame($expected, ApiErrorCodeEnum::findByStatusCode($case->getStatusCode()));
        }
        self::assertSame(15, ApiErrorCodeEnum::ParamsIsEmpty->getStatusCode());
        self::assertSame(ApiErrorCodeEnum::TwoFactorCodeRequired, ApiErrorCodeEnum::findByStatusCode(506001));
        self::assertNull(ApiErrorCodeEnum::findByStatusCode(506006));
        self::assertNull(ApiErrorCodeEnum::findByStatusCode(999));
    }
}
