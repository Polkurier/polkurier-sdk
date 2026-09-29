<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * The error codes an API error can carry.
 *
 * An error names its condition twice: as the `error_code` string, which is the case's value, and
 * as the numeric `status_code`, which statusCode() returns and fromStatusCode() maps back. Case
 * names follow SDK conventions. The values are the exact strings the API returns, including its
 * spellings: TOKEN_IS_NO_ACTIV and UNKNOWN_API_METOD are correct as written. The high-numbered
 * status codes reuse HTTP status numbers, but they arrive in the JSON body, not as the HTTP status.
 *
 * Both fields are optional in an error payload, so don't assume either is present.
 */
enum ApiErrorCodeEnum: string
{
    case UnknownError = 'UNKNOWN_ERROR';
    case JsonSyntaxError = 'JSON_SYNTAX_ERROR';
    case InputDataEmpty = 'INPUT_DATA_EMPTY';
    case EmptyAuthData = 'EMPTY_AUTH_DATA';
    case EmptyLogin = 'EMPTY_LOGIN';
    case EmptyToken = 'EMPTY_TOKEN';
    case InvalidAuthData = 'ERROR_AUTH_DATA';
    case TokenNotActive = 'TOKEN_IS_NO_ACTIV';
    case UnknownApiMethod = 'UNKNOWN_API_METOD';
    case NoInputObject = 'NO_INPUT_OBJECT';
    case ParamsNotSet = 'PARAMS_NOT_SET';
    case ParcelsAreTooBig = 'PARCELS_ARE_TO_BIG';
    case ParcelParameterFromSet = 'PARCEL_PARAMETER_FROM_SET';
    case CourierNameNotSet = 'COURIER_NAME_NOT_SET';
    case ParamsIsEmpty = 'PARAMS_IS_EMPTY';
    case NonLoggedUser = 'NON_LOGGED_USER';
    case UserDoesNotExist = 'USER_DOES_NOT_EXIST';
    case InvalidAuthMethod = 'INVALID_AUTHORIZATION_METHOD';
    case MaintenanceMode = 'MAINTENANCE_MODE';
    case AdditionalServiceUnavailable = 'ADDITIONAL_SERVICE_UNAVAILABLE';
    case ParameterInvalid = 'PARAMETER_INVALID';
    case ParameterOutOfRange = 'PARAMETER_OUT_OF_RANGE';
    case ValidationError = 'VALIDATION_ERROR';
    case FileUploadFail = 'FILE_UPLOAD_FAIL';
    case EmptyValuation = 'EMPTY_VALUATION';
    case FormExpired = 'FORM_EXPIRED';
    case TwoFactorAlreadySent = 'TWO_FACTOR_ALREADY_SENT';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case UnsupportedMediaType = 'UNSUPPORTED_MEDIA_TYPE';
    case NotFound = 'NOT_FOUND';
    case Forbidden = 'FORBIDDEN';
    case InternalServerError = 'INTERNAL_SERVER_ERROR';
    case InvalidOrderNumber = 'INVALID_ORDER_NUMBER';

    /**
     * The numeric `status_code` for this condition.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return match ($this) {
            self::UnknownError => 1,
            self::JsonSyntaxError => 2,
            self::InputDataEmpty => 3,
            self::EmptyAuthData => 4,
            self::EmptyLogin => 5,
            self::EmptyToken => 6,
            self::InvalidAuthData => 7,
            self::TokenNotActive => 8,
            self::UnknownApiMethod => 9,
            self::NoInputObject => 10,
            self::ParamsNotSet => 11,
            self::ParcelsAreTooBig => 12,
            self::ParcelParameterFromSet => 13,
            self::CourierNameNotSet => 14,
            self::ParamsIsEmpty => 15,
            self::NonLoggedUser => 16,
            self::UserDoesNotExist => 20,
            self::InvalidAuthMethod => 21,
            self::MaintenanceMode => 22,
            self::AdditionalServiceUnavailable => 23,
            self::ParameterInvalid => 24,
            self::ParameterOutOfRange => 25,
            self::ValidationError => 26,
            self::FileUploadFail => 27,
            self::EmptyValuation => 28,
            self::FormExpired => 30,
            self::TwoFactorAlreadySent => 31,
            self::InvalidOrderNumber => 400,
            self::Forbidden => 403,
            self::NotFound => 404,
            self::MethodNotAllowed => 405,
            self::UnsupportedMediaType => 415,
            self::TooManyRequests => 429,
            self::InternalServerError => 500,
        };
    }

    /**
     * The case for a numeric `status_code`, or null for a code this SDK doesn't know.
     *
     * @param int $statusCode
     *
     * @return self|null
     */
    public static function fromStatusCode(int $statusCode): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->statusCode() === $statusCode) {
                return $case;
            }
        }

        return null;
    }
}
