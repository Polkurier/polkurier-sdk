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
 * Thrown for missing or wrong credentials, an inactive token and an unknown authorization type.
 * Retrying won't help. The credentials have to change.
 *
 * Detected from `status_code` or `error_code`, whichever the response has. Either one can be
 * missing.
 *
 * @api
 */
class AuthorizationException extends WebserviceException
{
    /**
     * The codes this exception is raised for.
     *
     * {@see ApiErrorCodeEnum::rejectsCredentials()} decides; this lists the same cases for a
     * caller that wants them as strings.
     */
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
        return ApiErrorCodeEnum::fromStatusCode($status)?->rejectsCredentials() === true;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public static function isAuthorizationErrorCode(string $code): bool
    {
        return ApiErrorCodeEnum::tryFrom($code)?->rejectsCredentials() === true;
    }
}
