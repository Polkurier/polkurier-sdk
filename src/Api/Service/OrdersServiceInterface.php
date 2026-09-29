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
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\GetDocumentMessage;
use Polkurier\Webservice\Request\Order\GetOrderFileMessage;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Request\Order\GetStatusMessage;
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

/**
 * Placing, pricing, tracking and cancelling orders.
 *
 * Every method returns a {@see ResponseWrapper}. Get the parsed payload with
 * `getParsedResponse()->getResponse()`. Each method names its payload type.
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
     * address. Don't use this to check whether an order can be placed.
     *
     * Endpoint: `order_valuation_v2`
     *
     * Request: the same `data` object as createOrder(), below.
     *
     * Response: quotes keyed by carrier. Their shape differs between carriers, so the SDK returns the
     * decoded JSON as is.
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "UPS": {"price_net": 19.50, "price_gross": 23.99, "currency": "PLN"}
     *     }
     * }
     * ```
     *
     * @param CreateOrderMessage $createOrderMessage
     *
     * @return array<array-key, mixed> quotes keyed by carrier, shape differs per carrier
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function orderValuation(CreateOrderMessage $createOrderMessage): array;

    /**
     * Places an order.
     *
     * Not idempotent. Each accepted call books a separate shipment. If the call times out, treat the
     * result as unknown (not as failed) and check with getOrders() before retrying.
     *
     * The API does not always report every fault in a payload at once. A rejection lists at least
     * one problem, but maybe not all of them.
     *
     * Keep the description short. A cash-on-delivery amount of zero means the order ships with no
     * collection.
     *
     * Some requirements depend on the carrier: whether a pickup date or a time window is needed, and
     * whether either address needs a pickup point. `available_carriers` reports them.
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
     *     "pickup": {"pickupdate": "2026-09-24", "pickuptimefrom": "10:00", "pickuptimeto": "14:00"},
     *     "courierservice": {"SMS_NOTIFICATION_RECIPIENT": true},
     *     "additional_fields": {},
     *     "coupon_code": ""
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
     *                 "sender": {"person": "…", "street": "Testowa 1", "streetname": "Testowa",
     *                            "housenumber": "1", "postcode": "98-270", "city": "Złoczew",
     *                            "country": "PL", "point_id": "", "point": null},
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
     * Filtering by an order number that does not exist returns an empty page:
     * ```json
     * {"status": "success",
     *  "response": {"totalrows": 0, "totalpages": 0, "currentpage": 1, "pagesize": 100, "result": []}}
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
     * PDF, so don't set a file format here.
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
     * A cancelled order gets an error instead of a status. Use getOrders() to read the state of a
     * cancelled order.
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
     * Checks whether this account can book one collection for several shipments.
     *
     * The answer depends on the account and on any order details sent with the request, so it can
     * differ between two sets of orders. Check it before each booking.
     *
     * Endpoint: `is_multi_pickup_available`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "is_multi_pickup_available",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
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
     * @return MultiPickupAvailabilityMessage the payload, see
     *         {@see \Polkurier\Webservice\Response\Order\MultiPickupAvailabilityMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function isMultiPickupAvailable(): MultiPickupAvailabilityMessage;

    /**
     * Books one courier collection covering several shipments.
     *
     * Unlike a per-order pickup, the date and both ends of the time window are always required,
     * whatever the carrier needs. The date must be YYYY-MM-DD and the times HH:MM.
     *
     * Send each waybill number once. The list must not be empty or hold blank entries, and the SDK
     * checks this before sending.
     *
     * Expect partial success. Each waybill can be refused on its own (e.g. unknown order, not
     * eligible for a collective pickup, pickup already booked) while the others go through. Check
     * both lists. The API reports success even when the success list is empty.
     *
     * Successes are listed by order number, but failures are keyed by the waybill number you sent.
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
     *         "orders": ["6211000015757", "6211000015758"]
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
     *             "6211000015758": "Podjazd już został zamówiony"
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
     * The content hash stays the same across downloads, so you can use it to skip fetching a file you
     * have already cached.
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
