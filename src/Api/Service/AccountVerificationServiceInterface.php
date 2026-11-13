<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Account\RegisterAccountVerificationMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Identity verification for the account.
 *
 * `get_account_info` reports an account that has to be verified as
 * `require_agreement_verification`. An external provider runs the verification: you register an
 * attempt and get a token back, the customer completes the provider's flow with that token, and you
 * read the outcome back here.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 */
interface AccountVerificationServiceInterface
{
    /**
     * Starts an identity verification, or returns the one in progress, with its access token.
     *
     * A verification that is neither cancelled nor expired is returned instead of starting a new
     * one.
     *
     * The provider's flow needs the access token, and only this response contains it. A token stays
     * valid for up to three hours; calling again after that returns a new one.
     *
     * You can pass an order number to tie the verification to a shipment. Without one, a general
     * account verification starts.
     *
     * Endpoint: `register_account_verification`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "register_account_verification",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orderno": "12345-678"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "verification_id": "0a1b2c3d4e5f60718293a4b5c6d7e8f901234567…",
     *         "type": "online",
     *         "status": "NEW",
     *         "access_token": "a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 400, "error_code": "INVALID_ORDER_NUMBER",
     *  "response": "Błędny numer zamówienia"}
     * ```
     * The order number names none of the account's orders.
     *
     * @param RegisterAccountVerificationMessage|null $registerAccountVerificationMessage null
     *        starts a general account verification
     *
     * @return AccountVerificationMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function register(
        ?RegisterAccountVerificationMessage $registerAccountVerificationMessage = null
    ): AccountVerificationMessage;

    /**
     * Reports the most recent verification attempt and its outcome.
     *
     * Only the latest attempt is returned. An account that has never attempted verification gets an
     * empty response, which the SDK maps to null.
     *
     * The access token is not included here. Only register() returns it.
     *
     * Endpoint: `get_account_verification_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_account_verification_info",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "verification_id": "0a1b2c3d4e5f60718293a4b5c6d7e8f901234567…",
     *         "type": "online",
     *         "status": "NEW"
     *     }
     * }
     * ```
     *
     * Response when nothing has been attempted yet (the SDK reports this as null):
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * @return AccountVerificationMessage|null payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage}, or null when
     *         nothing has been attempted yet
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getInfo(): ?AccountVerificationMessage;
}
