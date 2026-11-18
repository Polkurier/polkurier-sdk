<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Geo\FindCityMessage;
use Polkurier\Webservice\Dto\Response\Geo\CityMessage;
use Polkurier\Webservice\Dto\Response\Geo\TokenMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Geographic lookups for address entry and pickup point selection.
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
interface GeoServiceInterface
{
    /**
     * Suggests localities for a postcode, e.g. to autocomplete an address form.
     *
     * Polish postcodes only: any other country, like an unknown postcode, gives an empty list. For
     * Poland the SDK checks the NN-NNN format before sending.
     *
     * One postcode can cover several localities.
     *
     * Endpoint: `find_city`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "find_city",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"postcode": "61-001", "country": "PL"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {"name": "Poznań"}
     *     ]
     * }
     * ```
     *
     * @param FindCityMessage $findCityMessage
     *
     * @return CityMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function findCity(FindCityMessage $findCityMessage): array;

    /**
     * Issues a short-lived token for the pickup point map widget.
     *
     * Every call returns a new token, which expires 4 hours after it's issued. Pass it to the map
     * front-end, which authenticates with it directly. Unlike the API token, it is safe to expose in
     * a browser.
     *
     * Endpoint: `get_map_token`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_map_token",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9…"}
     * }
     * ```
     *
     * @return TokenMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getMapToken(): TokenMessage;
}
