<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Common\TokenMessage;
use Polkurier\Webservice\Request\TwoFactorToken\ChangeTransportMethodMessage;
use Polkurier\Webservice\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Response\TwoFactorToken;
use Polkurier\Webservice\Response\TwoFactorToken\ValidityMessage;

/**
 * Two-factor confirmation for operations that need more than the API token.
 *
 * Register a token for the operation, have the user enter the code sent to them, and verify it.
 * Then repeat the original call with the token and code. The main operation that needs this is
 * changing where cash-on-delivery funds are paid.
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
 * With the default configuration the SDK throws these as exceptions. Each method lists the ones it
 * can throw.
 */
interface TwoFactorTokenServiceInterface
{
    /**
     * Starts a confirmation and sends a code to the customer.
     *
     * The token is bound to the namespace you register it under, and only that operation accepts
     * it. A token for a bank account change can't be used for anything else.
     *
     * The code is sent as part of this call. If it can't be delivered, you get an error, so a
     * successful response means the code was sent.
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
     *         "phone_number": "***600700",
     *         "transport_method": {"id": "…", "name": "SMS", "type": "SMS", "description": "",
     *                              "type_name": "SMS"},
     *         "operation_title": "Zmiana numeru konta bankowego",
     *         "operation_number": 1,
     *         "operation_date": "2026-09-23T09:10:13+02:00",
     *         "sent_at": "2026-09-23T09:10:13+02:00",
     *         "expires_at": "2026-09-23T09:25:13+02:00",
     *         "available_methods": [{"id": "…", "name": "SMS", "type": "SMS", "description": "",
     *                                "type_name": "SMS"}]
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
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT", "response": "…"}
     * ```
     * Thrown when a code was sent too recently. `get_system_info` reports the resend window.
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
     * Response: the same token object that registration returned, with a new `sent_at`.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT", "response": "…"}
     * ```
     *
     * @param TokenMessage $tokenMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenResend(TokenMessage $tokenMessage): TwoFactorToken;

    /**
     * Checks the code the customer entered.
     *
     * A wrong code, or an unknown or expired token, does not throw. It comes back as a successful
     * response with `valid` set to false. Always check the flag.
     *
     * Verifying doesn't use up the token. The confirmed operation does, so you pass the token and
     * code to it afterwards. Verifying twice does no harm. Codes have a fixed length, which
     * `get_system_info` reports.
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
     * An empty `code` gives the same error. Only a missing parameter is an error here. A wrong value
     * gives `valid: false`.
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
     * Use this when the customer can't receive the code on the first channel. The token returned by
     * registration lists their channels under `available_methods`.
     *
     * Switching resends the code on the new channel, so the same throttle as twoFactorTokenResend()
     * applies.
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
     * ```json
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT", "response": "…"}
     * ```
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
