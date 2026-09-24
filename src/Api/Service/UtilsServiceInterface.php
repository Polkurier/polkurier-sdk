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
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Response\Util\AuthConfirmationMessage;
use Polkurier\Webservice\Response\Util\HeartbeatMessage;

/**
 * Checks that do not belong to any one functional area.
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
interface UtilsServiceInterface
{
    /**
     * Confirms the API is reachable and reports its clock.
     *
     * The only method that needs no credentials, which makes it the right probe for connectivity and
     * for verifying a configured API URL before credentials exist. The SDK omits the authorization
     * block entirely rather than sending empty credentials.
     *
     * The returned time is the server's, so comparing it with your own is a cheap way to catch clock
     * skew — which matters for anything scheduling a collection.
     *
     * Endpoint: `heartbeat`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "heartbeat"
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "time": "2026-09-23T09:10:13+02:00"
     *     }
     * }
     * ```
     *
     * @return HeartbeatMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function heartbeat(): HeartbeatMessage;

    /**
     * Verifies a set of credentials without doing anything else.
     *
     * Takes the credentials explicitly rather than using the configured ones, so it can validate a
     * token a customer has just pasted in before it is stored anywhere.
     *
     * Authorization is enforced before any method runs, so bad credentials surface as an
     * AuthorizationException rather than as a negative result. The payload's flag is consequently
     * always true — reaching it at all is the answer.
     *
     * Endpoint: `test_auth_api`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "test_auth_api",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"authorization": true}
     * }
     * ```
     *
     * On bad credentials, which raises AuthorizationException rather than returning:
     * ```json
     * {
     *     "status": "error",
     *     "status_code": 4,
     *     "error_code": "EMPTY_AUTH_DATA",
     *     "response": "Brak danych do autoryzacji"
     * }
     * ```
     *
     * @param AuthorizationMessage $authorization the credentials to test
     *
     * @return AuthConfirmationMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function testAuthApi(AuthorizationMessage $authorization): AuthConfirmationMessage;
}
