<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * The `error_code` string an API error carries.
 *
 * Case names follow SDK conventions; the backing values are the literal strings the API emits and
 * must match them exactly, including its own spellings — TOKEN_IS_NO_ACTIV and UNKNOWN_API_METOD
 * are correct as written. Each case has a numeric counterpart of the same name in
 * {@see ApiStatusCodeEnum}.
 *
 * Both fields are optional in an error payload, so never assume either is present.
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
}
