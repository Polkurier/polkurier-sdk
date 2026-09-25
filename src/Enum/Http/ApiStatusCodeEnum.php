<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * Has the same cases as {@see ApiErrorCodeEnum}. The high-numbered cases reuse HTTP status codes,
 * which the API returns in the `status_code` field of the JSON body, not as the HTTP status.
 */
enum ApiStatusCodeEnum: int
{
    case UnknownError = 1;
    case JsonSyntaxError = 2;
    case InputDataEmpty = 3;
    case EmptyAuthData = 4;
    case EmptyLogin = 5;
    case EmptyToken = 6;
    case InvalidAuthData = 7;
    case TokenNotActive = 8;
    case UnknownApiMethod = 9;
    case NoInputObject = 10;
    case ParamsNotSet = 11;
    case ParcelsAreTooBig = 12;
    case ParcelParameterFromSet = 13;
    case CourierNameNotSet = 14;
    case ParamsIsEmpty = 15;
    case NonLoggedUser = 16;
    case UserDoesNotExist = 20;
    case InvalidAuthMethod = 21;
    case MaintenanceMode = 22;
    case AdditionalServiceUnavailable = 23;
    case ParameterInvalid = 24;
    case ParameterOutOfRange = 25;
    case ValidationError = 26;
    case FileUploadFail = 27;
    case EmptyValuation = 28;
    case FormExpired = 30;
    case TwoFactorAlreadySent = 31;
    case InvalidOrderNumber = 400;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case UnsupportedMediaType = 415;
    case TooManyRequests = 429;
    case InternalServerError = 500;
}
