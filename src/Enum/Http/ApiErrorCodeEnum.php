<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * The error codes an API error can carry.
 *
 * An error names its condition twice: as the `error_code` string, which is the case's value, and
 * as the numeric `status_code`, which statusCode() returns and fromStatusCode() maps back. The
 * values are the exact strings the API returns, including its spellings: TOKEN_IS_NO_ACTIV and
 * UNKNOWN_API_METOD are correct as written. The high-numbered status codes reuse HTTP status
 * numbers, but they arrive in the JSON body, not as the HTTP status.
 *
 * Both fields are optional in an error payload. `error_code` is the more precise of the two: a
 * status code can stand for more than one condition, e.g. 506006.
 * The two-factor conditions use the 506xxx numbers, except TWO_FACTOR_ALREADY_SENT.
 *
 * @api
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
    case TwoFactorCodeRequired = 'TWO_FACTOR_CODE_REQUIRED';
    case TwoFactorCodeExpired = 'TWO_FACTOR_CODE_EXPIRED';
    case TwoFactorSmsTemporarilyUnavailable = 'TWO_FACTOR_CANNOT_SEND_SMS_MESSAGE_TEMPORARY';
    case TwoFactorPhoneNumberNotValid = 'TWO_FACTOR_NUMBER_NOT_VALID_FOR_TWO_FACTOR';
    case TwoFactorInvalidCode = 'TWO_FACTOR_INVALID_CODE';
    case TwoFactorEmailNotValid = 'TWO_FACTOR_EMAIL_NOT_VALID_FOR_TWO_FACTOR';
    case TwoFactorInvalidMethod = 'TWO_FACTOR_INVALID_METHOD';
    case TwoFactorMaxTokensExceeded = 'TWO_FACTOR_MAX_TOKENS_EXCEEDED';
    case TwoFactorTransportMethodDoesNotExist = 'TWO_FACTOR_TRANSPORT_METHOD_DOES_NOT_EXIST';
    case TwoFactorNoValidSmsPhoneNumber = 'TWO_FACTOR_NO_VALID_SMS_PHONE_NUMBER_EXCEPTION';

    /**
     * Whether this condition means the credentials were rejected.
     *
     * Retrying does not help; the credentials have to change. The SDK raises
     * {@see \Polkurier\Webservice\Exception\AuthorizationException} for exactly these.
     *
     * @return bool
     */
    public function rejectsCredentials(): bool
    {
        return match ($this) {
            self::EmptyAuthData,
            self::EmptyLogin,
            self::EmptyToken,
            self::InvalidAuthData,
            self::TokenNotActive,
            self::InvalidAuthMethod => true,
            default => false,
        };
    }

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
            self::TwoFactorCodeRequired => 506001,
            self::TwoFactorCodeExpired => 506002,
            self::TwoFactorSmsTemporarilyUnavailable => 506003,
            self::TwoFactorPhoneNumberNotValid => 506004,
            self::TwoFactorInvalidCode => 506005,
            self::TwoFactorEmailNotValid => 506006,
            self::TwoFactorInvalidMethod => 506006,
            self::TwoFactorMaxTokensExceeded => 506006,
            self::TwoFactorTransportMethodDoesNotExist => 506007,
            self::TwoFactorNoValidSmsPhoneNumber => 506012,
        };
    }

    /**
     * The case for a numeric `status_code`.
     *
     * @param int $statusCode
     *
     * @return self|null null for a code this SDK doesn't know, and for one that stands for more than
     *         one condition. Use the `error_code` then
     */
    public static function fromStatusCode(int $statusCode): ?self
    {
        $matches = \array_values(\array_filter(
            self::cases(),
            static fn(self $case): bool => $case->statusCode() === $statusCode,
        ));

        return \count($matches) === 1 ? $matches[0] : null;
    }
}
