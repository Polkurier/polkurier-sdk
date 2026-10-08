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
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Request\Order\GetOrderFileMessage;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Request\Order\MultiPickupRequestMessage;
use Polkurier\Webservice\Request\Order\OrderIdMessage;
use Polkurier\Webservice\Response\Order\CancellationMessage;
use Polkurier\Webservice\Response\Order\CreatedOrderMessage;
use Polkurier\Webservice\Response\Order\DocumentMessage;
use Polkurier\Webservice\Response\Order\MultiPickupAvailabilityMessage;
use Polkurier\Webservice\Response\Order\MultiPickupResultMessage;
use Polkurier\Webservice\Response\Order\OrderFileMessage;
use Polkurier\Webservice\Response\Order\OrderListMessage;
use Polkurier\Webservice\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Response\Order\ValuationMessage;

/**
 * Placing, pricing, tracking and cancelling orders.
 *
 * Every method returns a {@see \Polkurier\Webservice\Http\Response\ResponseWrapper}. Get the parsed
 * payload with `getParsedResponse()->getResponse()`. Each method names its payload type.
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
interface OrdersServiceInterface
{
    /**
     * Prices an order without placing it.
     *
     * Takes the same payload as createOrder(). A payload that gets a price can still be rejected by
     * createOrder(), usually because the carrier will not accept the pickup or cannot route the
     * address. So a price doesn't show whether an order can be placed.
     *
     * Endpoint: `order_valuation_v2`
     *
     * Request: the same `data` object as createOrder(), below. With $courier, `returnvaluations` is
     * sent next to the order's fields, and only that carrier is priced:
     * ```json
     * {
     *     "apimethod": "order_valuation_v2",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"courier": "UPS", "shipmenttype": "box", "packs": […], "sender": {…},
     *              "recipient": {…}, "returnvaluations": "DPD"}
     * }
     * ```
     *
     * Response without $courier: one quote per carrier that can price the order. A carrier that can't carry the
     * shipment, e.g. because a parcel is too heavy for it, is left out of the list.
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "servicecode": "INPOST",
     *             "serviceName": "InPost Kurier",
     *             "netprice": 15.12,
     *             "grossprice": 18.6,
     *             "conditional_price_nett": 0,
     *             "conditional_price_gross": 0,
     *             "promotion_nett": 0,
     *             "promotion_gross": 0,
     *             "rebate_nett": 0,
     *             "rebate_gross": 0,
     *             "shipment": true,
     *             "available": true,
     *             "unavailable_message": ""
     *         }
     *     ]
     * }
     * ```
     *
     * Response with $courier: that carrier's quote alone, as an object and without `serviceName`.
     * The SDK returns it as a list of one:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "servicecode": "DPD",
     *         "netprice": 14.5,
     *         "grossprice": 17.84,
     *         "conditional_price_nett": 0,
     *         "conditional_price_gross": 0,
     *         "promotion_nett": 0,
     *         "promotion_gross": 0,
     *         "rebate_nett": 0,
     *         "rebate_gross": 0,
     *         "shipment": true,
     *         "available": true,
     *         "unavailable_message": ""
     *     }
     * }
     * ```
     *
     * A carrier code the API doesn't know is rejected, with neither code field:
     * ```json
     * {"status": "error", "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS, UPS_ES, …]"}
     * ```
     *
     * @param CreateOrderMessage $createOrderMessage
     * @param string|null $courier a service code from
     *        {@see \Polkurier\Webservice\Api\Service\CourierServiceInterface::availableCarriers()} to
     *        price the order for that carrier only, null for every carrier
     *
     * @return ValuationMessage[] one quote per carrier, or the one carrier's quote, see
     *         {@see \Polkurier\Webservice\Response\Order\ValuationMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function orderValuation(CreateOrderMessage $createOrderMessage, ?string $courier = null): array;

    /**
     * Places an order.
     *
     * Not idempotent. Each accepted call books a separate shipment. If the call times out, treat the
     * result as unknown (not as failed) and check with getOrders() before retrying. An order sent
     * with `external_application_id` and `external_order_id` can be looked up by them. The SDK
     * refuses one without the other.
     *
     * The API does not always report every fault in a payload at once. A rejection lists at least
     * one problem, but maybe not all of them.
     *
     * The description has a length limit. A cash-on-delivery amount of zero means the order ships
     * with no collection.
     *
     * Some requirements depend on the carrier: whether a pickup date or a time window is needed,
     * and whether either address needs a pickup point. `available_carriers` reports them.
     * {@see CreateOrderMessage} documents the rules for services that depend on other fields.
     *
     * The response is a short receipt. Use getOrders() to read the full order. The waybill list can
     * still be empty if the carrier has not issued one yet.
     *
     * Endpoint: `create_order`
     *
     * Request with every accepted `data` key. Only courier, shipmenttype, packs, sender and recipient
     * are required:
     * ```json
     * {
     *     "courier": "UPS",
     *     "shipmenttype": "box",
     *     "description": "Books",
     *     "insurance": 500,
     *     "packs": [
     *         {"length": 30, "width": 20, "height": 15, "weight": 2.5, "amount": 1, "type": "ST"}
     *     ],
     *     "sender": {
     *         "person": "Jan Kowalski",
     *         "company": "Acme sp. z o.o.",
     *         "street": "Długa",
     *         "housenumber": "12",
     *         "flatnumber": "",
     *         "postcode": "61-001",
     *         "city": "Poznań",
     *         "country": "PL",
     *         "email": "jan@example.com",
     *         "phone": "500600700"
     *     },
     *     "recipient": {
     *         "person": "Anna Nowak",
     *         "street": "Krótka",
     *         "housenumber": "5",
     *         "flatnumber": "3",
     *         "postcode": "00-001",
     *         "city": "Warszawa",
     *         "country": "PL",
     *         "email": "anna@example.com",
     *         "phone": "600700800"
     *     },
     *     "COD": {
     *         "codamount": 250.00,
     *         "codtype": "S",
     *         "codbankaccount": "34102010260000000000000001",
     *         "return_cod": "BA"
     *     },
     *     "cover_address": {
     *         "person": "Jan Kowalski",
     *         "company": "Acme Store",
     *         "street": "Rynek 1",
     *         "flat_number": "",
     *         "postcode": "61-001",
     *         "city": "Poznań",
     *         "email": "sklep@example.com",
     *         "phone": "500600700"
     *     },
     *     "pickup": {"pickupdate": "2026-09-24", "pickuptimefrom": "10:00", "pickuptimeto": "14:00"},
     *     "courierservice": {"SMS_NOTIFICATION_RECIPIENT": true, "COVER_ADDRESS_SENDER": true},
     *     "additional_fields": {},
     *     "coupon_code": "",
     *     "external_application_id": "6f1c2b9e-…",
     *     "external_order_id": "SHOP-1001",
     *     "external_order_number": "1001"
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "order_number": "12345-678",
     *         "consignid": "1Z999AA10123456784",
     *         "label": ["1Z999AA10123456784"],
     *         "price_net": 19.50,
     *         "price_gross": 23.99,
     *         "unpaid_amount": 23.99,
     *         "is_paid": false,
     *         "url_tracktrace": "https://www.ups.com/track?tracknum=1Z999AA10123456784",
     *         "shipment_code": null
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [sender]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 14, "error_code": "COURIER_NAME_NOT_SET",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze […]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 10, "error_code": "NO_INPUT_OBJECT",
     *  "response": "Brak parametru: [pickupdate]"}
     * ```
     * The carrier needs a collection date and none was given. `available_carriers` tells you which
     * carriers need one.
     * ```json
     * {"status": "error", "status_code": 23, "error_code": "ADDITIONAL_SERVICE_UNAVAILABLE",
     *  "response": "…"}
     * ```
     * The carrier does not offer the service. This includes WEEK_COLLECTION (weekend delivery) on
     * anything other than an InPost locker.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR", "response": "…"}
     * ```
     * A field failed validation, usually an address or a parcel dimension.
     *
     * @param CreateOrderMessage $createOrderMessage
     *
     * @return CreatedOrderMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\CreatedOrderMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createOrder(CreateOrderMessage $createOrderMessage): CreatedOrderMessage;

    /**
     * Lists orders, one page at a time.
     *
     * The response is always a paged envelope, even when you filter by a single order number. Pages
     * start at 1. The page size must be 1–100, and the SDK checks this before sending. The page size
     * key is `pagesize`. This method has no `limit` parameter.
     *
     * The parcel, item and file flags are off by default. Each one makes the call slower.
     *
     * Filters combine with AND. The date range applies to the order's creation date. The free-text
     * search matches order numbers, waybills and party names.
     *
     * Each address has `streetname`, `housenumber` and `flatnumber` split from `street`. They're
     * empty when the street can't be split, so rely on `street`. A cover address, when the order has
     * one, has no `email`, `machinename` or `point`, and its `point_id` is always empty.
     *
     * `external_application_id` lists only the orders placed with that application id, and
     * `external_order_id` narrows it to the one with your id. Both match exactly. The order id only
     * works together with the application id, and the SDK refuses it alone. No match gives an empty
     * page.
     *
     * Endpoint: `get_orders`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_orders",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "orderno": "",
     *         "status": "D",
     *         "courier": "UPS",
     *         "cod_min": 0,
     *         "cod_max": 1000,
     *         "date_from": "2026-01-01",
     *         "date_to": "2026-09-23",
     *         "search": "",
     *         "pagesize": 100,
     *         "page": 1,
     *         "external_application_id": "6f1c2b9e-…",
     *         "packs": true,
     *         "items": true,
     *         "files": false
     *     }
     * }
     * ```
     *
     * Response, with one order abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "totalrows": 2594,
     *         "totalpages": 26,
     *         "currentpage": 1,
     *         "pagesize": 100,
     *         "result": [
     *             {
     *                 "number": "12345-678",
     *                 "date": "2026-06-20T10:59:43+02:00",
     *                 "description": "Books",
     *                 "courier": "ETL",
     *                 "courier_name": "Geis",
     *                 "shipmenttype": "box",
     *                 "status": "Dostarczone",
     *                 "status_code": "D",
     *                 "status_date": "2026-06-25T17:20:00+02:00",
     *                 "delivered_date": "2026-06-25T09:00:00+02:00",
     *                 "insurance": 420,
     *                 "url": "https://www.geis.pl/pl/sledzenie-przesylki?packNumber=6211000015757",
     *                 "price_net": 119, "price_gross": 146.37,
     *                 "rebate_net": 0, "rebate_gross": 0, "vat_rate": 23,
     *                 "is_paid": true, "has_invoice": true, "unpaid_amount": 0,
     *                 "label": ["6211000015757"],
     *                 "waybills": [{"number": "6211000015757", "is_default": true}],
     *                 "sender": {"company": "", "person": "…", "street": "Testowa 1",
     *                            "streetname": "Testowa", "housenumber": "1", "flatnumber": "",
     *                            "postcode": "98-270", "city": "Złoczew", "phone": "…",
     *                            "email": "…", "country": "PL", "machinename": "",
     *                            "point_id": "", "point": null},
     *                 "recipient": {"…": "same shape as sender"},
     *                 "cover_address": null,
     *                 "COD": {"codtype": "S", "codamount": 420, "status": "PAID",
     *                         "return_cod": "BA", "return_details": []},
     *                 "pickup": {"pickupdate": "2026-06-21", "pickuptimefrom": "10:59",
     *                            "pickuptimeto": "10:59", "nocourierorder": false,
     *                            "multiPickup": false, "pickup_number": null},
     *                 "courierservice": ["Pobranie (COD)", "Ubezpieczenie przesyłki"],
     *                 "packs": [{"length": 175, "width": 67, "height": 47, "weight": 15,
     *                            "amount": 1, "type": "ST", "price_net": 89, "price_gross": 109.47}],
     *                 "items": [{"description": "Paczka nr 1", "amount": 1, "price_net": 89,
     *                            "value_nett": 89, "value_gross": 109.47, "type": "PACK",
     *                            "service_id": null}],
     *                 "external_application_id": null,
     *                 "external_order_id": null,
     *                 "external_order_number": ""
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * Filtered by an order number, the response is the order itself instead of a page. The SDK
     * returns it as a page with that one order, so the result has the same shape either way.
     *
     * Filtering by an order number that does not exist is an error, not an empty page. It has
     * neither `status_code` nor `error_code`:
     * ```json
     * {"status": "error", "response": "Nie znaleziono zlecenia [12345-999]"}
     * ```
     *
     * @param GetOrdersMessage|null $getOrdersMessage null lists the first page with no filters
     *
     * @return OrderListMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\OrderListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getOrders(?GetOrdersMessage $getOrdersMessage = null): OrderListMessage;

    /**
     * Cancels an order.
     *
     * Whether an order can be cancelled depends on how far the shipment has got and on the carrier's
     * rules. If cancellation is refused, the API returns an error with the carrier's reason. The
     * `cancellation` flag in a successful response is always true, so rely on the lack of an
     * exception instead.
     *
     * Endpoint: `cancel_order`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "cancel_order",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orderno": "12345-678"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"cancellation": true}
     * }
     * ```
     *
     * Common errors. None of them has a code:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] jest pusty"}
     * ```
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * ```json
     * {"status": "error", "response": "Brak zamówienia 99999-1"}
     * ```
     * The last one is returned both for an order that doesn't exist and for one that belongs to
     * another account. When the carrier refuses a cancellation, its reason comes back in the same
     * shape.
     *
     * @param OrderIdMessage $orderIdMessage
     *
     * @return CancellationMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\CancellationMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function cancelOrder(OrderIdMessage $orderIdMessage): CancellationMessage;

    /**
     * Fetches shipping labels as one document.
     *
     * You can request several order numbers at once. Their labels come back merged into one file,
     * which is handy for a day's dispatch.
     *
     * The SDK checks the format of each order number before sending. The file holds labels only for
     * active orders on your account, so it can hold fewer labels than you asked for. The response
     * doesn't say which are missing. Compare against what you sent if that matters.
     *
     * Labels come back as a base64-encoded PDF unless you ask for a printer language, in which case
     * you get that instead.
     *
     * Carriers stop serving labels for old shipments. When that happens you get a generic
     * UNKNOWN_ERROR.
     *
     * Endpoint: `get_label`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_label",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "orderno": ["12345-678", "12345-679"],
     *         "file_format": "ZPL"
     *     }
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"}
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] jest pusty"}
     * ```
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * Returned only when every number is malformed.
     * ```json
     * {"status": "error", "response": "Brak etykiet do pobrania"}
     * ```
     * The numbers are well-formed, but none of them gives a usable label (unknown, owned by another
     * account, or cancelled).
     * ```json
     * {"status": "error", "status_code": 1, "error_code": "UNKNOWN_ERROR",
     *  "response": "Nieznany błąd. Proszę spróbować ponownie"}
     * ```
     * Usually an old shipment whose label the carrier no longer serves.
     *
     * @param GetDocumentMessage $getDocumentMessage
     *
     * @return DocumentMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\DocumentMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getLabel(GetDocumentMessage $getDocumentMessage): DocumentMessage;

    /**
     * Fetches the handover protocol (the list the courier signs on collection).
     *
     * Takes several order numbers, with the same rules as getLabel(). Always returns a base64-encoded
     * PDF, whatever file format is set.
     *
     * Endpoint: `get_protocol`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_protocol",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orderno": ["12345-678", "12345-679"]}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"}
     * }
     * ```
     *
     * @param GetDocumentMessage $getDocumentMessage
     *
     * @return DocumentMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\DocumentMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getProtocol(GetDocumentMessage $getDocumentMessage): DocumentMessage;

    /**
     * Current delivery status of one order, optionally with its scan history.
     *
     * A cancelled order gets an error instead of a status. getOrders() reads the state of a cancelled
     * order.
     *
     * The scan history is off by default. Asking for it makes the call slower.
     *
     * Watch the date formats. There are 3 here, and get_orders uses none of them. The status
     * timestamp has no seconds, the delivery date has no time, and the scan timestamps in the history
     * are full datetimes.
     *
     * Endpoint: `get_status`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_status",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orderno": "12345-678", "tracking": true}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "status": "Dostarczone",
     *         "status_code": "D",
     *         "status_date": "2026-06-25 17:20",
     *         "delivered_date": "2026-06-25",
     *         "url": "https://www.geis.pl/pl/sledzenie-przesylki?packNumber=6211000015757",
     *         "tracking": [
     *             {
     *                 "status_date": "2026-06-25 09:00:00",
     *                 "status_code": "D",
     *                 "status": "Dostarczone",
     *                 "tracking_status": "DELIVERED",
     *                 "location": "Warszawa",
     *                 "description": "Przesyłka doręczona"
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * Common errors. None of them has a code:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * ```json
     * {"status": "error", "response": "Brak zamówienia 12345-999999"}
     * ```
     * ```json
     * {"status": "error", "response": "Zamówienie anulowane"}
     * ```
     * The last one is what you get for a cancelled order.
     *
     * @param GetStatusMessage $getStatusMessage
     *
     * @return OrderStatusMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\OrderStatusMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getStatus(GetStatusMessage $getStatusMessage): OrderStatusMessage;

    /**
     * Checks whether a collective pickup can be booked for a shipment.
     *
     * The answer depends on the shipment, chiefly its carrier and parcels, so ask for each shipment
     * before offering one collection for several. Which carriers and shipments qualify can change.
     *
     * It describes a shipment, not an order you've already placed. To book a collective pickup,
     * place the order with {@see \Polkurier\Webservice\Request\Order\PickupMessage::$multiPickup}
     * set, then pass its number to createMultiOrderPickup().
     *
     * The API answers false, not an error, when it can't evaluate the request. So false can also
     * mean the request was incomplete. The SDK checks that the carrier and at least one parcel are
     * set before sending.
     *
     * Endpoint: `is_multi_pickup_available`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "is_multi_pickup_available",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "courier": "DPD",
     *         "senderPostCode": "31-042",
     *         "shipmentType": "box",
     *         "packs": [{"length": 40, "width": 30, "height": 20, "weight": 0.55, "type": "ST", "amount": 1}]
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"isMultiPickupAvailable": true}
     * }
     * ```
     *
     * @param MultiPickupAvailabilityRequestMessage $multiPickupAvailabilityRequestMessage
     *
     * @return MultiPickupAvailabilityMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\MultiPickupAvailabilityMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function isMultiPickupAvailable(
        MultiPickupAvailabilityRequestMessage $multiPickupAvailabilityRequestMessage
    ): MultiPickupAvailabilityMessage;

    /**
     * Books one courier collection covering several orders.
     *
     * Pass order numbers, not waybill numbers. An order can join only if it was placed for a
     * collective pickup, with
     * {@see \Polkurier\Webservice\Request\Order\PickupMessage::$multiPickup} set, has been
     * accepted, and has no collection booked yet. Use isMultiPickupAvailable() to see whether a
     * shipment can be placed that way.
     *
     * Unlike a per-order pickup, the date and both ends of the time window are always required,
     * whatever the carrier needs. The date must be YYYY-MM-DD and the times HH:MM. They apply to
     * every order in the call.
     *
     * The list must not be empty, and every entry must be an order number. The SDK checks this
     * before sending. Duplicates are ignored.
     *
     * Expect partial success. Each order can be refused on its own while the others go through, so
     * check both lists. The API reports success even when the success list is empty. Successes are
     * listed by order number. Failures are keyed by the order number, usually as you sent it, with
     * one of these messages:
     *
     * - "Nie znaleziono zamówienia": no such order on this account, or it wasn't placed for a
     *   collective pickup
     * - "Nie można zamówić podjazdu zbiorczo dla tego zamówienia": not placed for a collective
     *   pickup, or not accepted yet
     * - "Podjazd już został zamówiony": its collection is already booked
     *
     * Endpoint: `create_multi_order_pickup`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "create_multi_order_pickup",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "pickupdate": "2026-09-24",
     *         "pickuptimefrom": "10:00",
     *         "pickuptimeto": "14:00",
     *         "orders": ["12345-678", "12345-679"]
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "success": ["12345-678"],
     *         "failed": {
     *             "12345-679": "Podjazd już został zamówiony"
     *         }
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 11, "error_code": "PARAMS_NOT_SET",
     *  "response": "Brak parametru lub jest w nieprawidłowym formacie: [pickupdate] prawidłowy format [YYYY-MM-DD]."}
     * ```
     * ```json
     * {"status": "error", "status_code": 11, "error_code": "PARAMS_NOT_SET",
     *  "response": "Brak parametru lub jest w nieprawidłowym formacie: [pickuptimefrom] prawidłowy format [HH:MM]."}
     * ```
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [orders]"}
     * ```
     *
     * @param MultiPickupRequestMessage $multiPickupRequestMessage
     *
     * @return MultiPickupResultMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\MultiPickupResultMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createMultiOrderPickup(
        MultiPickupRequestMessage $multiPickupRequestMessage
    ): MultiPickupResultMessage;


    /**
     * Downloads a document attached to an order.
     *
     * Besides the label and protocol, an order can have other documents attached, such as customs
     * paperwork, proof of delivery or correspondence. Get the filenames from getOrders() with the
     * file flag turned on.
     *
     * The content hash stays the same across downloads, so it tells a file you already have from a
     * changed one.
     *
     * Endpoint: `get_order_file`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_order_file",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orderno": "12345-678", "filename": "potwierdzenie.pdf"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "name": "potwierdzenie.pdf",
     *         "type": "application/pdf",
     *         "size": 102400,
     *         "hash": "6f1d…",
     *         "date_created": "2026-09-23T09:10:13+02:00",
     *         "file": "JVBERi0xLjQKJeLjz9MK…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * Returned for an unknown order, an unknown filename, and an order that belongs to another
     * account.
     *
     * @param GetOrderFileMessage $getOrderFileMessage
     *
     * @return OrderFileMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\OrderFileMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getOrderFile(GetOrderFileMessage $getOrderFileMessage): OrderFileMessage;

}
