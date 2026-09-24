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
use Polkurier\Webservice\Request\Geo\FindCityMessage;
use Polkurier\Webservice\Response\Geo\CityMessage;
use Polkurier\Webservice\Response\Geo\TokenMessage;

/**
 * Geographic lookups supporting address entry and point selection.
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
interface GeoServiceInterface
{
    /**
     * Suggests localities for a postcode, for autocompleting an address form.
     *
     * Polish postcodes only. A country other than Poland, and a postcode not matching the NN-NNN
     * pattern, both come back as an empty list rather than as an error. An empty result therefore means
     * either "no such postcode" or "this input was never going to match", with no way to tell them
     * apart — validate the pattern yourself if that distinction matters.
     *
     * A postcode can cover several localities, hence a list.
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
     * A fresh token is minted on every call; nothing is cached or reused. It is meant to be handed to
     * the map front-end, which authenticates with it directly, so it can be given to a browser where
     * the API token cannot.
     *
     * Endpoint: `get_map_token`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_map_token",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
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
     * @throws WebserviceException
     */
    public function getMapToken(): TokenMessage;
}
