<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\TwoFactorToken\ChangeTransportMethodMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\ResendMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Dto\Response\TwoFactorToken\ValidityMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Two-factor confirmation for operations that need more than the API token.
 *
 * Register a token for the operation, have the user enter the code sent to them, and verify it.
 * Then repeat the original call with the token and code. Operations that need it include
 * registering or changing a bank account and making a phone number the default.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither:
 * ```json
 * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
 *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
 * ```
 * ```json
 * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
 * ```
 * The SDK throws these as exceptions. Each method lists the ones it can throw.
 *
 * @api
 */
interface TwoFactorTokenServiceInterface
{
    /**
     * Starts a confirmation and sends a code to the customer.
     *
     * The token is bound to the namespace you register it under, and only that operation accepts
     * it.
     *
     * The code goes to the first channel the namespace takes: authenticator app, text message to
     * the default phone, e-mail. Only LOGIN takes e-mail; ENABLE_TWO_FACTOR_LOGIN,
     * DISABLE_TWO_FACTOR_LOGIN, CREATE_TWO_FACTOR_TRANSPORT_METHOD and
     * DELETE_TWO_FACTOR_TRANSPORT_METHOD take text messages only. With an authenticator app nothing
     * is sent.
     *
     * Success means the code was sent, or is ready in the authenticator app. With no usable
     * channel, e.g. no default phone that takes text messages, the call fails with
     * TWO_FACTOR_NO_VALID_SMS_PHONE_NUMBER_EXCEPTION.
     *
     * At most 10 active tokens per account; the next fails with TWO_FACTOR_MAX_TOKENS_EXCEEDED.
     *
     * The response has the token id (pass it when verifying), the expiry time, and the delivery
     * channels available to this customer. twoFactorTokenChangeTransportMethod() picks from those
     * channels.
     *
     * Endpoint: `two_factor_token_register`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "two_factor_token_register",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"namespace": "CHANGE_BANK_ACCOUNT_NUMBER"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "8f3c1e2a-…",
     *         "phone_number": "",
     *         "transport_method": {"id": "…", "name": "*** *** 700", "type": "SMS",
     *                              "description": "Kod autoryzacyjny wysyłany w wiadomości SMS",
     *                              "type_name": "Wiadomość SMS"},
     *         "operation_title": "Zmiana numeru konta bankowego",
     *         "operation_number": 1,
     *         "operation_date": "2026-09-23T09:10:13+02:00",
     *         "sent_at": "2026-09-23T09:10:13+02:00",
     *         "expires_at": "2026-09-23T09:13:13+02:00",
     *         "available_methods": [{"id": "…", "name": "*** *** 700", "type": "SMS",
     *                                "description": "Kod autoryzacyjny wysyłany w wiadomości SMS",
     *                                "type_name": "Wiadomość SMS"}]
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Brak parametru lub błędna wartość parametru: [namespace]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 506006, "error_code": "TWO_FACTOR_MAX_TOKENS_EXCEEDED",
     *  "response": "Przekroczono maksymalną ilość aktywnych sesji weryfikacji dwuskładnikowej."}
     * ```
     * ```json
     * {"status": "error", "status_code": 506012, "error_code": "TWO_FACTOR_NO_VALID_SMS_PHONE_NUMBER_EXCEPTION",
     *  "response": "Brak domyślnego numeru telefonu lub numer nie jest numerem krajowej sieci mobilnej. Aby dodać domyślny numer dla weryfikacji dwuskładnikowej skontaktuj się z BOK"}
     * ```
     *
     * @param RegisterMessage $registerMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenRegister(RegisterMessage $registerMessage): TwoFactorToken;

    /**
     * Sends the code again for a token already registered.
     *
     * Resending is throttled. A request inside the resend window fails with TWO_FACTOR_ALREADY_SENT.
     * `get_system_info` reports the window in seconds under `security.two_factor_resend_throttle`,
     * so you can disable a resend button for that long.
     *
     * Endpoint: `two_factor_token_resend`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "two_factor_token_resend",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"token": "8f3c1e2a-…"}
     * }
     * ```
     *
     * Response: the token, with a new `sent_at` and the same `expires_at`.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT",
     *  "response": "Kod został już wysłany. Odczekaj 60 sekund i spróbuj ponownie."}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Błędny token sesji lub sesja wygasła"}
     * ```
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [token]"}
     * ```
     *
     * @param ResendMessage $resendMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenResend(ResendMessage $resendMessage): TwoFactorToken;

    /**
     * Checks the code the customer entered.
     *
     * A wrong code, or an unknown or expired token, does not throw. It comes back as a successful
     * response with `valid` set to false.
     *
     * Verifying doesn't use up the token. The confirmed operation does, so you pass the token and
     * code to it afterwards. Verifying twice does no harm. `get_system_info` reports the length of
     * the codes the API sends.
     *
     * Endpoint: `two_factor_token_verify`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "two_factor_token_verify",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"token": "8f3c1e2a-…", "code": "123456"}
     * }
     * ```
     *
     * Response for a correct code:
     * ```json
     * {"status": "success", "response": {"valid": true}}
     * ```
     *
     * Response for a wrong code or an unknown or expired token (still a success envelope):
     * ```json
     * {"status": "success", "response": {"valid": false}}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [token]"}
     * ```
     * An empty `code` gives the same error.
     *
     * @param VerifyMessage $verifyMessage
     *
     * @return ValidityMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenVerify(VerifyMessage $verifyMessage): ValidityMessage;

    /**
     * Switches a pending confirmation to a different delivery channel.
     *
     * For a customer who can't receive the code on the first channel. The token returned by
     * registration lists their channels under `available_methods`.
     *
     * The code goes out on the new channel at once; the resend window doesn't apply.
     *
     * Endpoint: `two_factor_token_change_transport_method`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "two_factor_token_change_transport_method",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"token": "8f3c1e2a-…", "method": "…"}
     * }
     * ```
     *
     * Response: the updated token, with the new channel and a new `sent_at`.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [token]"}
     * ```
     * An empty `method` gives the same error.
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nieznana metoda uwierzytelnienia"}
     * ```
     * The method isn't one of the customer's channels. An unknown token gives NOT_FOUND "Błędny
     * token sesji lub sesja wygasła".
     *
     * @param ChangeTransportMethodMessage $changeTransportMethodMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenChangeTransportMethod(
        ChangeTransportMethodMessage $changeTransportMethodMessage
    ): TwoFactorToken;
}
