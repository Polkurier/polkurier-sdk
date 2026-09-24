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
use Polkurier\Webservice\Request\Courier\AvailableCarriersMessage;
use Polkurier\Webservice\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Request\Courier\PickupMessage;
use Polkurier\Webservice\Response\Carrier;
use Polkurier\Webservice\Response\Country;
use Polkurier\Webservice\Response\CourierPoint;
use Polkurier\Webservice\Response\Courier\PickupResponseMessage;
use Polkurier\Webservice\Response\Courier\PickupTimeMessage;

/**
 * Carriers, pickups, pickup points and supported countries.
 *
 * Start with availableCarriers(): the carrier codes it returns are what the order methods expect,
 * and its additional data describes which services, pack types and pickup rules each carrier
 * supports — the constraints the API will enforce when an order is placed.
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
interface CourierServiceInterface
{
    /**
     * Lists the carriers this account may ship with.
     *
     * The list is per account, not global: it reflects the contracts and integrations enabled for the
     * caller, so a carrier the service supports in general can be absent. A handful of carriers are
     * excluded from the API surface altogether regardless of account.
     *
     * The `servicecode` of each entry is the value to pass as `courier` everywhere else.
     *
     * Requesting the additional data attaches, per carrier, the services it sells, the pack and shipment
     * types it accepts, its pickup rules, its points map and its carrier-specific additional fields — in
     * other words the constraints the API will enforce when an order is placed. It makes the response
     * several times larger, so fetch it when building a shipping form rather than on every request. One
     * detail appears only with it: whether the carrier requires insurance alongside cash on delivery.
     *
     * Filtering by carrier is applied after the list is assembled, so an unknown code yields an empty
     * list rather than an error.
     *
     * Endpoint: `available_carriers`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "available_carriers",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"additional_data": false, "returncarrier": "UPS"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {"servicecode": "UPS", "name": "UPS", "foreign_shipments": false}
     *     ]
     * }
     * ```
     *
     * With `additional_data: true`, each entry additionally carries an `additional_data` object holding
     * `COD`, `pickup`, `shipment_type`, `options`, `points_map` and `additional_fields`.
     *
     * @param AvailableCarriersMessage|null $availableCarriersMessage null lists every carrier
     *        without the additional data
     *
     * @return Carrier[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function availableCarriers(
        ?AvailableCarriersMessage $availableCarriersMessage = null
    ): array;

    /**
     * Books a courier collection.
     *
     * The carrier is contacted synchronously, so this is a real booking rather than a queued request,
     * and how long it takes depends on the carrier. Omitting the date books for today.
     *
     * The reply describes the day, not the booking: `pickupdate` reports whether the carrier accepted
     * collection on that date, and `time` lists the windows it offered. Carriers that do not work in
     * windows accept the date and return an empty list, so an empty list is not a refusal — read the
     * flag.
     *
     * Call getCourierPickupTime() first if you need to offer the customer a choice of day; this method
     * commits to one.
     *
     * Endpoint: `pickup_courier`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "pickup_courier",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "courier": "DPD",
     *         "shipfrom": "61-001",
     *         "shipto": "00-001",
     *         "parcel": "box",
     *         "pickupdate": "2026-09-24"
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "pickupdate": true,
     *         "time": [
     *             {"timefrom": "10:00", "timeto": "14:00"},
     *             {"timefrom": "14:00", "timeto": "18:00"}
     *         ]
     *     }
     * }
     * ```
     *
     *
     * A carrier that refuses the requested day answers successfully with the flag cleared:
     * ```json
     * {"status": "success", "response": {"pickupdate": false, "time": []}}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [courier]"}
     * ```
     * Carrier-side refusals surface as the carrier's own message with no code attached.
     * @param PickupMessage $pickupMessage
     *
     * @return PickupResponseMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Courier\PickupResponseMessage}
     */
    public function pickupCourier(PickupMessage $pickupMessage): PickupResponseMessage;

    /**
     * Lists the days a carrier can collect on, with the windows available each day.
     *
     * The API finds these by probing the carrier day by day from today until it has enough acceptable
     * dates or runs out of attempts, so days the carrier refuses are simply absent rather than reported
     * as unavailable. Results are cached per carrier, parcel type and postcode pair, which makes repeat
     * calls cheap but means a booking made elsewhere may not be reflected immediately.
     *
     * If every probe fails the API raises the carrier's own error rather than returning an empty list,
     * so an empty list means the carrier answered but offered nothing.
     *
     * Endpoint: `get_courier_pickup_time`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_courier_pickup_time",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"courier": "DPD", "shipfrom": "61-001", "shipto": "00-001", "parcel": "box"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "pickupdate": "2026-09-24",
     *             "time": [{"timefrom": "10:00", "timeto": "14:00"}]
     *         },
     *         {
     *             "pickupdate": "2026-09-25",
     *             "time": [{"timefrom": "09:00", "timeto": "17:00"}]
     *         }
     *     ]
     * }
     * ```
     *
     *
     * Common errors:
     * ```json
     * {"status": "error", "response": "Brak kodu pocztowego nadawcy"}
     * ```
     * Raised when `shipfrom` is missing. If every probed day fails, the carrier's own error is
     * returned rather than an empty list.
     * @param GetPickupTimeMessage $getPickupTimeMessage
     *
     * @return PickupTimeMessage[] payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Courier\PickupTimeMessage}[]
     */
    public function getCourierPickupTime(GetPickupTimeMessage $getPickupTimeMessage): array;

    /**
     * Lists the countries one international carrier delivers to.
     *
     * International carriers only. A domestic carrier code is rejected with the acceptable set named in
     * the message — currently UPS_EX, DPDEXP, GLS_FOREIGN and INPOST_INTERNATIONAL. Use
     * getSupportedCountries() for the full country list.
     *
     * The payload is a map of ISO country code to Polish country name, not a list of objects, and a
     * country known under several names has them joined with ", " into one string. It carries no
     * regions; getSupportedCountries() does.
     *
     * Endpoint: `get_countries`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_countries",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"couriers": "DPDEXP"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "GB": "Anglia, Irlandia północna, Szkocja",
     *         "AT": "Austria",
     *         "BE": "Belgia",
     *         "CZ": "Czechy"
     *     }
     * }
     * ```
     *
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
     * ```
     * ```json
     * {"status": "error",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS_EX, DPDEXP, GLS_FOREIGN, INPOST_INTERNATIONAL]"}
     * ```
     * The second is what a domestic carrier code gets, and it arrives without either code field.
     * @param GetCountriesMessage $getCountriesMessage
     *
     * @return array<string, string> <string, string>, ISO code to name
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCountries(GetCountriesMessage $getCountriesMessage): array;

    /**
     * Lists every country the service supports, with its regions.
     *
     * Not carrier-specific and not a delivery guarantee — it is the reference list an address form
     * should offer. For where a particular carrier actually delivers, use getCountries().
     *
     * Cheap to call, and stable enough to be worth caching locally. As with getCountries(), a country
     * known under several names has them joined into one string. The region ids are what an address's
     * province field accepts.
     *
     * Endpoint: `get_supported_countries_v2`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_supported_countries_v2",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "PL",
     *             "name": "Polska",
     *             "is_ue": true,
     *             "is_default": true,
     *             "provinces": [
     *                 {"id": "WP", "name": "wielkopolskie"}
     *             ]
     *         }
     *     ]
     * }
     * ```
     *
     * @return Country[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getSupportedCountries(): array;

    /**
     * Finds pickup points across one or more carriers.
     *
     * At least one carrier must be named; the API rejects an empty list. Three search modes, and the
     * shape of the reply follows from which you use:
     *
     * - By id — returns that one point as a single object, or an empty result if no carrier in the list
     *   knows the code. The SDK asks for a single object in this case and a list otherwise.
     * - By search phrase — matches against the point details and returns up to the first thousand
     *   matches. Paging is ignored in this mode.
     * - Neither — walks the whole filtered set with `page` and `limit`.
     *
     * Filtering by function narrows to points supporting all of the functions given, not any.
     *
     * This is the right call for lookups and searches. The per-carrier bulk listings exist for mirroring
     * a whole network locally.
     *
     * Endpoint: `get_courier_point`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_courier_point",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "couriers": ["INPOST_PACZKOMAT"],
     *         "functions": ["cod", "send"],
     *         "searchquery": "",
     *         "page": 1,
     *         "limit": 100
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "ADA01M",
     *             "type": "parcel_locker",
     *             "name": "",
     *             "provider": "INPOST_PACZKOMAT",
     *             "city": "Adamów",
     *             "zip": "21-412",
     *             "street": "Kościuszki 27",
     *             "address": "21-412 Adamów, Kościuszki 27",
     *             "description": "Przy sklepie Lewiatan",
     *             "latitude": 51.73834,
     *             "longitude": 22.26405,
     *             "cod": true,
     *             "available": true,
     *             "status": "Operating",
     *             "send": true,
     *             "collect": true,
     *             "openingHours": "24/7",
     *             "visible": true,
     *             "requireApp": false,
     *             "requireAppMessage": "",
     *             "functions": ["cod", "collect", "send"],
     *             "countryiso": "PL",
     *             "courierId": 15
     *         }
     *     ]
     * }
     * ```
     *
     * Searching by id returns the same object unwrapped, not inside a list.
     *
     *
     * Searching by id returns the object unwrapped rather than inside a list. A miss is reported as
     * an empty array, which the SDK maps to null:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * An unknown carrier code is likewise not an error — it simply matches nothing.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
     * ```
     * Raised when `couriers` is missing or empty; at least one carrier must always be named.
     * @param GetPointMessage $getPointMessage the search; its id must not be set
     *
     * @return CourierPoint[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function findCourierPoints(GetPointMessage $getPointMessage): array;

    /**
     * One pickup point by its identifier.
     *
     * The same endpoint as {@see self::findCourierPoints()}, which answers with a single point
     * rather than a list when an id is given. An id that matches nothing is not an error: the API
     * answers with an empty payload and this returns null.
     *
     * The carrier list is required even here, and an empty one is rejected outright — the id is
     * looked up within the carriers named, not across all of them.
     *
     * Endpoint: `get_courier_point`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_courier_point",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"couriers": ["INPOST_PACZKOMAT"], "id": "POZ01A"}
     * }
     * ```
     *
     * Response: a single point, in the same shape the list form returns its entries.
     *
     * @param string $id the point's identifier, as `findCourierPoints()` reports it
     * @param string[] $couriers the carriers to look it up in; must not be empty
     *
     * @return CourierPoint|null null when no point carries that id
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCourierPoint(string $id, array $couriers): ?CourierPoint;

}
