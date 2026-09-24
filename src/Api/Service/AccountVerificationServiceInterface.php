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
use Polkurier\Webservice\Request\Account\RegisterAccountVerificationMessage;
use Polkurier\Webservice\Response\Account\AccountVerificationMessage;

/**
 * Identity verification for the account.
 *
 * Some accounts must be verified before they can ship, which `get_account_info` reports as
 * `require_account_verification`. Verification is carried out by an external provider: the API
 * registers an attempt and hands back a token, the customer completes the provider's flow with it,
 * and the outcome is read back here.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface AccountVerificationServiceInterface
{
    /**
     * Starts an identity verification and returns the token needed to complete it.
     *
     * The access token is only present on this response, never when reading the verification back,
     * and it is what the provider's flow requires. Treat it as a one-time secret: hand it straight
     * on rather than storing it.
     *
     * An order number may be supplied to tie the verification to the shipment that prompted it;
     * without one a general account verification is started.
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
     * @param RegisterAccountVerificationMessage|null $registerAccountVerificationMessage null
     *        starts a general account verification
     *
     * @return AccountVerificationMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\AccountVerificationMessage}
     */
    public function register(
        ?RegisterAccountVerificationMessage $registerAccountVerificationMessage = null
    ): AccountVerificationMessage;

    /**
     * Reports the most recent verification attempt and its outcome.
     *
     * Only the latest attempt is reported; earlier ones are not retrievable.
     *
     * An account that has never attempted verification gets an empty response, which the SDK maps
     * to null rather than to an object with blank fields — so null means "never attempted" and is
     * distinct from an attempt that failed.
     *
     * The access token is never included here, only when registering.
     *
     * Endpoint: `get_account_verification_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_account_verification_info",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
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
     * Response when nothing has ever been attempted — the SDK reports this as null:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * @return AccountVerificationMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\AccountVerificationMessage} or null
     */
    public function getInfo(): AccountVerificationMessage;
}
