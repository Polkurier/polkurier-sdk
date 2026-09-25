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
 * Start with availableCarriers(). The order methods expect the carrier codes it returns. Its
 * additional data lists the services, pack types and pickup rules each carrier supports, which the
 * API enforces when an order is placed.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP
 * status. `status_code` and `error_code` are both optional. An error may have one, both or
 * neither:
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
interface CourierServiceInterface
{
    /**
     * Lists the carriers this account may ship with.
     *
     * The list depends on the contracts and integrations enabled for your account, so a carrier the
     * service supports in general may be missing.
     *
     * Pass an entry's `servicecode` as `courier` in the other methods.
     *
     * With the additional data, each carrier also lists the services it sells, the pack and shipment
     * types it accepts, its pickup rules, its points map and its own additional fields. The API
     * enforces these when an order is placed. The response gets several times larger, so fetch it
     * when you build a shipping form, not on every request. Only the additional data tells you
     * whether the carrier requires insurance with cash on delivery.
     *
     * An unknown code in the carrier filter gives an empty list, not an error.
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
     * With `additional_data: true`, each entry also has an `additional_data` object with `COD`,
     * `pickup`, `shipment_type`, `options`, `points_map` and `additional_fields`.
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
     * This is a real booking. Without a date, the pickup is booked for today.
     *
     * The reply describes the day. `pickupdate` says whether the carrier accepted a collection on
     * that date, and `time` lists the windows it offered. Carriers that don't use windows accept the
     * date and return an empty list, so an empty list doesn't mean a refusal. Check the flag.
     *
     * To let the customer choose a day, call getCourierPickupTime() first. This method books the
     * date you give it.
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
     * If the carrier refuses the day, the call still succeeds, with the flag set to false:
     * ```json
     * {"status": "success", "response": {"pickupdate": false, "time": []}}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [courier]"}
     * ```
     * Errors from the carrier come back as the carrier's own message, with no code.
     *
     * @param PickupMessage $pickupMessage
     *
     * @return PickupResponseMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Courier\PickupResponseMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function pickupCourier(PickupMessage $pickupMessage): PickupResponseMessage;

    /**
     * Lists the days a carrier can collect on, with the windows available each day.
     *
     * The list starts from today. Days the carrier refuses are left out.
     *
     * Errors from the carrier come back as the carrier's own message. An empty list means the carrier
     * offered no days.
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
     * Common errors:
     * ```json
     * {"status": "error", "response": "Brak kodu pocztowego nadawcy"}
     * ```
     * `shipfrom` is missing.
     *
     * @param GetPickupTimeMessage $getPickupTimeMessage
     *
     * @return PickupTimeMessage[] a list of
     *         {@see \Polkurier\Webservice\Response\Courier\PickupTimeMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCourierPickupTime(GetPickupTimeMessage $getPickupTimeMessage): array;

    /**
     * Lists the countries one international carrier delivers to.
     *
     * International carriers only. A domestic carrier code is rejected, and the error message lists
     * the accepted codes (currently UPS_EX, DPDEXP, GLS_FOREIGN and INPOST_INTERNATIONAL). Use
     * getSupportedCountries() for the full country list.
     *
     * The payload maps ISO country codes to Polish country names. If a country has several names,
     * they are joined with ", " into one string. There are no regions here. getSupportedCountries()
     * has them.
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
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
     * ```
     * ```json
     * {"status": "error",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS_EX, DPDEXP, GLS_FOREIGN, INPOST_INTERNATIONAL]"}
     * ```
     * You get the second one for a domestic carrier code. It has neither code field.
     *
     * @param GetCountriesMessage $getCountriesMessage
     *
     * @return array<string, string> country names keyed by ISO code
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
     * This is the reference list for an address form. It doesn't depend on the carrier and doesn't
     * mean every carrier delivers there. Use getCountries() to see where a given carrier delivers.
     *
     * The call is light and the list rarely changes, so you can cache it locally. As with
     * getCountries(), several names for one country are joined into one string. Use the region ids
     * in an address's province field.
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
     * Name at least one carrier. The SDK checks this before sending. There are 3 search modes, and
     * the reply's shape depends on which one you use:
     *
     * - By id: the API returns one point as a single object, not a list, so use getCourierPoint()
     *   for this. The message passed here must not have an id set.
     * - By search phrase: matches the point details and returns up to the first 1000 matches.
     *   Leave `page` and `limit` unset in this mode.
     * - Neither: pages through the whole filtered set with `page` and `limit`.
     *
     * The function filter keeps only points that support all of the functions given.
     *
     * Use this for lookups and searches. The per-carrier bulk listings are for copying a whole
     * network locally.
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
     * A search by id returns the object on its own, not inside a list. A miss comes back as an empty
     * array, which getCourierPoint() turns into null:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * An unknown carrier code is not an error either. It just matches nothing.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
     * ```
     * `couriers` is missing or empty.
     *
     * @param GetPointMessage $getPointMessage the search, with no id set
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
     * Fetches one pickup point by its id.
     *
     * Uses the same endpoint as {@see self::findCourierPoints()}. Given an id, the endpoint returns
     * a single point instead of a list. An id that matches nothing is not an error. The API returns
     * an empty payload and this method returns null.
     *
     * The carrier list is still required, and an empty one is rejected. The id is looked up only
     * within the carriers you name.
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
     * @param string[] $couriers the carriers to look it up in, must not be empty
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
