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
 * Two-factor confirmation for operations the API will not accept on a token alone.
 *
 * The flow is: register a token for the operation, have the user enter the code that is sent to
 * them, verify it, then repeat the original call carrying the token and code. Changing where
 * cash-on-delivery funds are paid is the main operation that requires this.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither:
 * ```json
 * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
 *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
 * ```
 * ```json
 * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
 * ```
 * With the default configuration the SDK raises these rather than returning them; see the
 * exception list on each method.
 */
interface TwoFactorTokenServiceInterface
{
    /**
     * Starts a confirmation and sends a code to the customer.
     *
     * The token is bound to the namespace it is registered under and is only accepted by that operation,
     * so one minted for a bank-account change cannot be spent elsewhere.
     *
     * Sending is part of the call: a message that cannot be delivered is an error rather than a token,
     * so a successful response means the code really went out.
     *
     * The response carries the token id to quote when verifying, when it expires, and the delivery
     * channels available to this customer — which is what the transport-method change works from.
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
     * Raised when a code was sent too recently; `get_system_info` reports the window.
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
     * Throttled: asking again inside the resend window is refused with TWO_FACTOR_ALREADY_SENT rather
     * than silently ignored. `get_system_info` reports the window in seconds under
     * `security.two_factor_resend_throttle`, so a client can disable its resend button for exactly that
     * long instead of guessing.
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
     * Response: the same token object registration returned, with a new `sent_at`.
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
     * Returns a verdict rather than raising: a wrong code, and an unknown or expired token, both come
     * back as a successful response with the flag cleared. Do not treat the absence of an exception as
     * success — read the flag.
     *
     * Verifying does not consume the token. The operation being confirmed does that, which is why the
     * token and code are passed on to it afterwards, and why verifying twice is harmless. Codes are a
     * fixed length, reported by `get_system_info`.
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
     * Response, on a correct code:
     * ```json
     * {"status": "success", "response": {"valid": true}}
     * ```
     *
     * Response, on a wrong code or an unknown or expired token — still a success envelope:
     * ```json
     * {"status": "success", "response": {"valid": false}}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [token]"}
     * ```
     * An empty `code` is reported the same way. Only a missing parameter is an error here; a wrong one
     * is a verdict.
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
     * For when the customer cannot receive the code the way it was first sent. The channels open
     * to them are listed on the token that registration returned, under `available_methods`.
     *
     * Switching re-sends the code down the new channel, so it is subject to the same throttle as
     * twoFactorTokenResend().
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
     * Response: the token as it now stands, with the new channel and a fresh `sent_at`.
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
