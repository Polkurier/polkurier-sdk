<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Polkurier\Webservice\Enum\Http\ApiErrorCodeEnum;

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
        $code = ApiErrorCodeEnum::fromStatusCode($status);

        return $code !== null && static::isAuthorizationErrorCode($code->value);
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
