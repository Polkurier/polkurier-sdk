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
 * Thrown for a missing, malformed, unknown or inactive token, and for a token used with an
 * authorization method the account isn't allowed to use. Retrying won't help. The credentials
 * have to change.
 *
 * Detected from `status_code` or `error_code`, whichever the response has. Either one can be
 * missing.
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
