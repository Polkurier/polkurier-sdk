<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;
use Polkurier\Webservice\Enum\Http\ApiStatusCodeEnum;

/**
 * The API rejected the credentials.
 *
 * Raised for a missing, malformed, unknown or inactive token, and for a token used with an
 * authorization method the account may not use. Retrying will not help; the credentials have to
 * change.
 *
 * Classified from whichever of `status_code` and `error_code` the response carried — either can be
 * absent, so neither can be relied on alone.
 *
 * @api
 */
class AuthorizationException extends WebserviceException
{
    public const array ERROR_STATUSES = [
        ApiStatusCodeEnum::EmptyAuthData->value,
        ApiStatusCodeEnum::EmptyLogin->value,
        ApiStatusCodeEnum::EmptyToken->value,
        ApiStatusCodeEnum::InvalidAuthData->value,
        ApiStatusCodeEnum::TokenNotActive->value,
        ApiStatusCodeEnum::InvalidAuthMethod->value,
    ];

    public const array ERROR_CODES = [
        ApiErrorCodeEnum::EmptyAuthData->value,
        ApiErrorCodeEnum::EmptyLogin->value,
        ApiErrorCodeEnum::EmptyToken->value,
        ApiErrorCodeEnum::InvalidAuthData->value,
        ApiErrorCodeEnum::TokenNotActive->value,
        ApiErrorCodeEnum::InvalidAuthMethod->value,
    ];

    /**
     * @param int $status
     *
     * @return bool
     */
    public static function isAuthorizationErrorStatus(int $status): bool
    {
        return \in_array($status, static::ERROR_STATUSES);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public static function isAuthorizationErrorCode(string $code): bool
    {
        return \in_array($code, static::ERROR_CODES);
    }
}
