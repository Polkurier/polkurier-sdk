<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Courier\AvailableCarriersMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Dto\Response\Carrier;
use Polkurier\Webservice\Dto\Response\Country;
use Polkurier\Webservice\Dto\Response\Courier\PickupResponseMessage;
use Polkurier\Webservice\Dto\Response\Courier\PickupTimeMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Carriers, pickups, pickup points and supported countries.
 *
 * Start with availableCarriers(). The order methods expect the carrier codes it returns.
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
 */
interface CourierServiceInterface
{
    /**
     * Lists the carriers this account may ship with.
     *
     * The list depends on the contracts and integrations enabled for your account, so a carrier the
     * service supports in general may be missing.
     *
     * Pass an entry's `servicecode` as `courier` in the order and pickup methods.
     *
     * With the additional data, each carrier also lists cash on delivery and insurance, the services
     * it sells, the shipment types it accepts, its pickup rules, its points map and its own
     * additional fields. Treat it as a guide for your shipping form: whether an order is accepted is
     * decided when it's placed. The response gets much larger, so fetch it when you build a shipping
     * form, not on every request. Only the additional data tells you whether the carrier requires
     * insurance with cash on delivery.
     *
     * An unknown code in the carrier filter gives an empty list.
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
     *         {"servicecode": "UPS", "name": "UPS Standard", "foreign_shipments": false}
     *     ]
     * }
     * ```
     *
     * With `additional_data: true`, each entry also has an `additional_data` object with `COD`,
     * `insurance`, `courierservice`, `shipmenttype`, `pickup`, `points_map` and
     * `additional_fields`. `points_map` can be null. Some keys inside `pickup` aren't sent for
     * every carrier, see {@see \Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Option}.
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
     * Checks whether a carrier can collect on one day, and in which time windows.
     *
     * Nothing is booked. A collection is booked with the order, through
     * {@see \Polkurier\Webservice\Entity\Order\CreateOrderMessage::getPickup()}, or for several
     * orders at once with
     * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::createMultiOrderPickup()}.
     * Without a date, today is checked.
     *
     * `pickupdate` says whether the carrier can collect that day; `time` lists its windows.
     * Carriers without windows return an empty list, so check the flag, not the list.
     *
     * getCourierPickupTime() lists the coming days the carrier can collect on.
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
     * Some carriers answer a refused day with `"response": []`, which the SDK reads the same way.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [courier]"}
     * ```
     * ```json
     * {"status": "error", "response": "Data podjazdu kuriera wcześniejsza niż aktualna"}
     * ```
     * ```json
     * {"status": "error", "response": "Data nie jest dniem roboczym"}
     * ```
     * The last 2 are a date in the past and a day that isn't a working day. Errors from the carrier
     * come back as the carrier's own message, with no code.
     *
     * @param PickupMessage $pickupMessage
     *
     * @return PickupResponseMessage the payload, see
     *         {@see \Polkurier\Webservice\Dto\Response\Courier\PickupResponseMessage}
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
     * The list starts from today and holds up to 5 days (1 for some carriers), looking at most 14
     * days ahead. Days the carrier refuses are left out.
     *
     * When no day is offered, you get either an empty list or an error, often with the carrier's own
     * message.
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
     *         {@see \Polkurier\Webservice\Dto\Response\Courier\PickupTimeMessage}
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
     * Only the carriers the error message lists are accepted (currently UPS_EX, DPDEXP, GLS_FOREIGN
     * and INPOST_INTERNATIONAL). Any other code is rejected. Use getSupportedCountries() for the
     * full country list.
     *
     * The payload maps ISO country codes to Polish country names. If a country has several names,
     * they are joined with ", " into one string. There are no regions here. getSupportedCountries()
     * has them.
     *
     * A carrier with no countries listed returns `[]`, read as an empty map.
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
     * You get the second one for any other carrier code. It has neither code field.
     *
     * @param GetCountriesMessage $getCountriesMessage
     *
     * @return array<string, string> country names keyed by ISO code, possibly empty
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
     * The list is sorted by Polish name and rarely changes, so you can cache it locally. As with
     * getCountries(), several names for one country are joined into one string. `is_default` is true
     * for Poland only. Use the region ids in the province field of an address book entry,
     * {@see \Polkurier\Webservice\Entity\Address\SaveAddressMessage::getProvince()}.
     *
     * Endpoint: `get_supported_countries_v2`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_supported_countries_v2",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "CA",
     *             "name": "Kanada",
     *             "is_ue": false,
     *             "is_default": false,
     *             "provinces": [
     *                 {"id": "AB", "name": "Alberta"},
     *                 {"id": "BC", "name": "British Columbia"}
     *             ]
     *         },
     *         {
     *             "id": "PL",
     *             "name": "Polska",
     *             "is_ue": true,
     *             "is_default": true,
     *             "provinces": []
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
     * Name at least one carrier. The SDK checks this before sending. Pass all the codes a carrier's
     * points map lists, from
     * {@see \Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\PointsMap::getAvailableProviders()}.
     * A code on its own may match nothing, as DPD_COURIER_POINT does, and so does an unknown code.
     *
     * There are 3 search modes, and the reply's shape depends on which one you use:
     *
     * - By id: the API returns one point as a single object, not a list, so use getCourierPoint()
     *   for this. The message passed here must not have an id set.
     * - By search phrase: matches the point's id, postcode, city, street and description, and
     *   returns up to the first 1000 matches. `page` and `limit` have no effect in this mode.
     * - Neither: pages through the whole filtered set with `page` and `limit`. Without `limit` the
     *   whole set comes back in one reply and `page` has no effect, so the SDK refuses a `page`
     *   without a `limit` before sending.
     *
     * The function filter keeps only points that support all of the functions given.
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
     * Uses the same endpoint as {@see self::findCourierPoints()}, which returns a single point when
     * given an id. An id that matches nothing gives an empty payload, and this method returns null.
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
     * Response: a single point, in the same shape the list form returns its entries. A miss:
     * ```json
     * {"status": "success", "response": []}
     * ```
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
