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
use Polkurier\Webservice\Request\Address\DeleteMessage;
use Polkurier\Webservice\Request\Address\GetMessage;
use Polkurier\Webservice\Request\Address\SaveMessage;
use Polkurier\Webservice\Response\Address;

/**
 * The account's address book.
 *
 * Saved addresses are a convenience for the panel and for integrations that reuse the same senders
 * — an order does not have to reference one. Placing an order takes a full address inline, so
 * nothing here is a prerequisite for shipping.
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
interface AddressesServiceInterface
{
    /**
     * Lists saved addresses.
     *
     * Senders and recipients are stored separately and fetched by separate queries; an unfiltered call
     * concatenates them into one list, senders first. An address saved under both roles is therefore
     * returned twice, as two entries with different ids.
     *
     * Sender listings include addresses shared with the account by others, not only its own.
     *
     * Note that the house and flat numbers come back split out of the street, even though they are sent
     * joined onto it.
     *
     * Endpoint: `get_addresses`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_addresses",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"type": "SENDER"}
     * }
     * ```
     * Omit `type`, or send it empty, to get both roles.
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "ySf",
     *             "name": "domyślny",
     *             "company": "Acme sp. z o.o.",
     *             "person": "Jan Kowalski",
     *             "street": "Długa",
     *             "housenumber": "1",
     *             "flatnumber": "",
     *             "postcode": "61-001",
     *             "city": "Poznań",
     *             "province": "",
     *             "country": "PL",
     *             "email": "jan@example.com",
     *             "phone": "500600700",
     *             "type": "SENDER",
     *             "default": false,
     *             "shared": false,
     *             "is_cover_address": false
     *         }
     *     ]
     * }
     * ```
     *
     * An account with no addresses gets `"response": []`.
     *
     * @param GetMessage|null $getMessage null lists both roles
     *
     * @return Address[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getAddresses(?GetMessage $getMessage = null): array;

    /**
     * Creates an address, or updates the one the message's id names.
     *
     * The role is mandatory and validated. An unknown country ISO code is rejected; an unknown province
     * code is not reported, and comes back as none — so a typo there is lost rather than flagged, and
     * is worth checking against the response.
     *
     * The house and flat numbers do not round-trip as separate fields, so what comes back may be
     * segmented differently from what went in. Compare the response rather than assuming it echoes the
     * request.
     *
     * A pickup point is only kept for recipient addresses and dropped for senders.
     *
     * Marking the address as default is applied after the save, as a second step, so an address can be
     * created successfully and still not become the default if that step fails.
     *
     * Endpoint: `save_address`
     *
     * Request; omit `id` to create:
     * ```json
     * {
     *     "apimethod": "save_address",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "id": "ySf",
     *         "type": "SENDER",
     *         "name": "domyślny",
     *         "company": "Acme sp. z o.o.",
     *         "person": "Jan Kowalski",
     *         "street": "Długa",
     *         "housenumber": "1",
     *         "flatnumber": "",
     *         "postcode": "61-001",
     *         "city": "Poznań",
     *         "province": "WP",
     *         "country": "PL",
     *         "email": "jan@example.com",
     *         "phone": "500600700",
     *         "point_id": "",
     *         "default": false,
     *         "is_cover_address": false
     *     }
     * }
     * ```
     *
     * Response: the stored address, in the same shape getAddresses() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [type]. Musi zawierać się w zbiorze [SENDER, RECIPIENT]"}
     * ```
     * Also what a missing `type` gives.
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [country]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"housenumber": ["…"]}}
     * ```
     * Field-level failures; read `validation_errors` for all of them at once.
     *
     * @param SaveMessage $saveMessage
     *
     * @return Address as stored, with its id
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveAddress(SaveMessage $saveMessage): Address;

    /**
     * Deletes one address-book entry.
     *
     * The role is part of the identity, not a filter: an address saved under both roles is only removed
     * from the role named here, and the other copy survives with its own id.
     *
     * Deletion is immediate and permanent. Orders already placed against the address are unaffected,
     * since they hold their own copy of it.
     *
     * Endpoint: `delete_address`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "delete_address",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "ySf", "type": "SENDER"}
     * }
     * ```
     *
     * Response — note the payload is an empty array rather than `true`; the SDK reports success as a
     * boolean regardless:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [id]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Wskazany adres nie istnieje"}
     * ```
     * Raised for an unknown id and for one that exists under the other role only. Unlike the bank
     * account and pack template deletes, this one does report a miss.
     *
     * @param DeleteMessage $deleteMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deleteAddress(DeleteMessage $deleteMessage): bool;
}
