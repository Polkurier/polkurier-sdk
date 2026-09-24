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
 * Every method returns a {@see ResponseWrapper}; the parsed payload is reached through
 * `getParsedResponse()->getResponse()`. The concrete payload type is named on each method.
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
interface OrdersServiceInterface
{
    /**
     * Prices an order without placing it.
     *
     * Takes the same payload as createOrder(), which does not make it a rehearsal for one: a quote is a
     * price, and a payload that prices cleanly can still be rejected on creation — most often for a
     * pickup the carrier will not accept or an address it cannot route. Do not use this to find out
     * whether an order is placeable.
     *
     * A missing sender is tolerated here and defaulted to Poland, and a missing description is filled
     * in, neither of which happens when placing an order.
     *
     * Endpoint: `order_valuation_v2`
     *
     * Request: the same `data` object as createOrder(), below.
     *
     * Response: carrier quotes, whose shape varies per carrier, which is why the SDK leaves the payload
     * as decoded JSON rather than modelling it.
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
     * @return array<array-key, mixed> quotes keyed by carrier; the shape varies per carrier
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
     * Not idempotent and not reversible by repeating: each accepted call books a separate shipment.
     * Treat a timeout as unknown rather than failed, and reconcile with getOrders() before retrying.
     *
     * Not every fault in a payload is reported at once, so treat a rejection as "at least this is
     * wrong" rather than as the complete list — which is part of why the SDK validates locally first.
     *
     * Three things are not reported as errors at all, and are worth getting right rather than
     * discovering later: an over-long description does not survive intact, a parcel type outside
     * {@see PackTypeEnum} is not honoured as sent, and a cash-on-delivery amount of zero means the
     * order ships with no collection.
     *
     * Several requirements depend on the carrier rather than the payload — whether a pickup date is
     * needed, whether a time window is needed, whether a pickup point is required on either address.
     * `available_carriers` reports them, and {@see CreateOrderMessage} documents the conditional service
     * rules.
     *
     * The response is a short receipt, not the order: read it back with getOrders() for the full record.
     * The waybill list can be empty at this point if the carrier has not yet issued one.
     *
     * Endpoint: `create_order`
     *
     * Request, showing every accepted `data` key; only courier, shipmenttype, packs, sender and
     * recipient are required:
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
     *
     * Common errors. Validation stops at the first problem, so a payload with several faults reports
     * them one call at a time:
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
     * Raised when the chosen carrier requires a collection date and none was given — which carriers
     * those are is reported by `available_carriers`.
     * ```json
     * {"status": "error", "status_code": 23, "error_code": "ADDITIONAL_SERVICE_UNAVAILABLE",
     *  "response": "…"}
     * ```
     * Raised for a service the carrier does not offer, including WEEK_COLLECTION on anything other
     * than an InPost locker.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR", "response": "…"}
     * ```
     * Field-level validation, most often on an address or a parcel dimension.
     * @param CreateOrderMessage $createOrderMessage
     *
     * @return CreatedOrderMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\CreatedOrderMessage}
     */
    public function createOrder(CreateOrderMessage $createOrderMessage): CreatedOrderMessage;

    /**
     * Lists orders, one page at a time.
     *
     * Always a paging envelope, even when filtering to a single order number. Page size is capped at
     * 100 and pages are 1-based; the size actually applied comes back in the envelope, so read it from
     * there rather than assuming it matches what was asked for. The page size key is `pagesize` —
     * `limit` is not a parameter of this method.
     *
     * The parcel, item and file flags are off by default and each adds to the work of the call, so a
     * page asking for all three is markedly slower than a plain listing.
     *
     * Filters combine with AND. The date range applies to the order's creation date, and the free-text
     * search matches order numbers, waybills and party names.
     *
     * One caveat worth coding against: if the server fails part-way through normalising a page it still
     * answers `success`, with the orders it managed alongside a total that counts all of them. A short
     * page that is not the last page should therefore be treated as suspect rather than as the end of
     * the data.
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
     *
     * Filtering to a number that does not exist is not an error — it is an empty page:
     * ```json
     * {"status": "success",
     *  "response": {"totalrows": 0, "totalpages": 0, "currentpage": 1, "pagesize": 100, "result": []}}
     * ```
     * @param GetOrdersMessage|null $getOrdersMessage null lists the first page unfiltered
     *
     * @return OrderListMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\OrderListMessage}
     */
    public function getOrders(?GetOrdersMessage $getOrdersMessage = null): OrderListMessage;

    /**
     * Cancels an order.
     *
     * The order number is validated for format, then for ownership — an order belonging to another
     * account is reported as missing rather than forbidden, so the two cases are indistinguishable from
     * the client.
     *
     * Whether cancellation is possible at all depends on how far the shipment has progressed and on the
     * carrier's own rules; the API raises an error carrying the carrier's reason when it is refused. The
     * `cancellation` flag in a successful response is therefore always true, and the absence of an
     * exception is the real signal.
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
     *
     * Common errors, none of which carry a code:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] jest pusty"}
     * ```
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * ```json
     * {"status": "error", "response": "Brak zamówienia 99999-1"}
     * ```
     * The last covers both a non-existent order and one belonging to another account — the two are
     * deliberately indistinguishable. A cancellation the carrier refuses returns its reason in the
     * same shape.
     * @param OrderIdMessage $orderIdMessage
     *
     * @return CancellationMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\CancellationMessage}
     */
    public function cancelOrder(OrderIdMessage $orderIdMessage): CancellationMessage;

    /**
     * Fetches shipping labels as one document.
     *
     * Several order numbers can be requested together and come back merged into a single file, which is
     * what makes this worth batching for a day's dispatch.
     *
     * The filtering is lenient in a way worth knowing: order numbers that are malformed, unknown, owned
     * by another account or cancelled are dropped without comment, and the call only fails when nothing
     * is left. A response can therefore contain fewer labels than were asked for, with no way to tell
     * from the response which were dropped — compare against what you sent if that matters.
     *
     * Labels come back as base64-encoded PDF unless a printer language is requested, in which case the
     * API emits that instead. An unrecognised format is ignored rather than rejected, yielding a PDF.
     *
     * Labels are not always available: carriers stop serving them for old shipments, which surfaces as a
     * generic UNKNOWN_ERROR rather than a specific one.
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
     *
     * Common errors:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] jest pusty"}
     * ```
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * Raised only when every number supplied is malformed; a mix of good and bad ones succeeds with
     * the bad ones dropped.
     * ```json
     * {"status": "error", "response": "Brak etykiet do pobrania"}
     * ```
     * Raised when the numbers parse but none of them resolves to a usable label — unknown, owned by
     * another account, or cancelled.
     * ```json
     * {"status": "error", "status_code": 1, "error_code": "UNKNOWN_ERROR",
     *  "response": "Nieznany błąd. Proszę spróbować ponownie"}
     * ```
     * Typically what an old shipment gives when the carrier no longer serves its label.
     * @param GetDocumentMessage $getDocumentMessage
     *
     * @return DocumentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\DocumentMessage}
     */
    public function getLabel(GetDocumentMessage $getDocumentMessage): DocumentMessage;

    /**
     * Fetches the handover protocol — the list the courier signs on collection.
     *
     * Batches the same way as getLabel() and applies the same silent filtering of unusable order
     * numbers. Always a base64-encoded PDF; the printer-language option is ignored here.
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
     * @return DocumentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\DocumentMessage}
     */
    public function getProtocol(GetDocumentMessage $getDocumentMessage): DocumentMessage;

    /**
     * Current delivery state of one order, optionally with its scan history.
     *
     * The status reflects the primary waybill once one exists and the order record before that, so a
     * freshly placed order reports its order state rather than a carrier state.
     *
     * A cancelled order is refused with an error rather than reported as cancelled — use getOrders() if
     * you need to read a cancelled order's state.
     *
     * The scan history is off by default, and asking for it makes the API query the carrier's tracking
     * service rather than read stored data, which is both slower and able to fail independently. It
     * comes back empty for a shipment the carrier has not scanned yet, and also when the tracking lookup
     * itself failed — the two are indistinguishable.
     *
     * Mind the date formats: three appear here, none of them the ones get_orders uses. The status
     * timestamp has no seconds, the delivery date has no time at all, and the scan timestamps inside the
     * history are full datetimes.
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
     *
     * Common errors, none of which carry a code:
     * ```json
     * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
     * ```
     * ```json
     * {"status": "error", "response": "Brak zamówienia 12345-999999"}
     * ```
     * ```json
     * {"status": "error", "response": "Zamówienie anulowane"}
     * ```
     * The last is what a cancelled order gets instead of a status.
     * @param GetStatusMessage $getStatusMessage
     *
     * @return OrderStatusMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\OrderStatusMessage}
     */
    public function getStatus(GetStatusMessage $getStatusMessage): OrderStatusMessage;

    /**
     * Whether this account may book one collection covering several shipments.
     *
     * Availability is decided from the account and from whatever order context is sent with the request,
     * so this is a precondition check rather than a fixed account capability — the answer can differ
     * between two sets of orders.
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
     * @return MultiPickupAvailabilityMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\MultiPickupAvailabilityMessage}
     */
    public function isMultiPickupAvailable(): MultiPickupAvailabilityMessage;

    /**
     * Books one courier collection covering several shipments.
     *
     * Unlike a per-order pickup, the date and both ends of the time window are all mandatory and are
     * rejected outright if missing or unparseable — the carrier is not consulted about what it needs.
     * The date must be YYYY-MM-DD and the times HH:MM.
     *
     * Waybill numbers are de-duplicated and blanks dropped before anything happens; an effectively empty
     * list is an error.
     *
     * Partial success is the normal outcome, not an edge case. Each waybill is processed separately and
     * can be refused on its own terms — unknown order, ineligible for collective pickup, pickup already
     * booked — while the others succeed. Both lists must be inspected: a response with an empty success
     * list is still reported as an API success.
     *
     * Note the asymmetry in the result: successes are listed by order number, failures keyed by the
     * waybill number that was sent.
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
     * The last is also what a list of only blanks gives, since they are stripped before the check.
     * @param MultiPickupRequestMessage $multiPickupRequestMessage
     *
     * @return MultiPickupResultMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\MultiPickupResultMessage}
     */
    public function createMultiOrderPickup(
        MultiPickupRequestMessage $multiPickupRequestMessage
    ): MultiPickupResultMessage;


    /**
     * Downloads a document attached to an order.
     *
     * Orders can carry documents beyond the label and protocol — customs paperwork, proof of
     * delivery, correspondence. The filenames come from the order listing, which reports them when
     * asked for files.
     *
     * The content hash is stable across downloads, so a client that caches can use it to skip
     * re-fetching.
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
     * Raised for an unknown order, an unknown filename on it, and an order belonging to another
     * account alike.
     *
     * @param GetOrderFileMessage $getOrderFileMessage
     *
     * @return OrderFileMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Order\OrderFileMessage}
     */
    public function getOrderFile(GetOrderFileMessage $getOrderFileMessage): OrderFileMessage;

}
