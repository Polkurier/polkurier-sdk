<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
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
interface UtilsServiceInterface
{
    /**
     * Confirms the API is reachable and reports its clock.
     *
     * It needs no credentials, so it checks connectivity, or a configured API URL before there are
     * credentials. The SDK leaves out the authorization block
     * entirely.
     *
     * The returned time is the server's. Compare it with your own clock to catch clock skew, which
     * matters when you schedule a collection.
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
     * Takes the credentials as an argument instead of using the configured ones, e.g. to check a token
     * a customer has just pasted in, before it is stored.
     *
     * Bad credentials throw an {@see \Polkurier\Webservice\Exception\AuthorizationException}. The
     * `authorization` flag in a successful response is therefore always true.
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
     * Failed authorization (this example has empty credentials) throws AuthorizationException:
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
