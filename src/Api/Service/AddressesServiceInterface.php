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
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Address\DeleteMessage;
use Polkurier\Webservice\Request\Address\GetMessage;
use Polkurier\Webservice\Request\Address\SaveMessage;
use Polkurier\Webservice\Response\Address;

/**
 * The account's address book.
 *
 * Saved addresses save you re-entering the same senders and recipients. An order doesn't have to
 * reference one: it takes the full address inline, so you can ship without using this service.
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
interface AddressesServiceInterface
{
    /**
     * Lists saved addresses.
     *
     * Without a `type` filter you get one list with senders first, then recipients. An address saved
     * under both roles appears twice, with two different ids.
     *
     * Sender listings also include addresses other accounts have shared with this one.
     *
     * Note: house and flat numbers come back as separate fields.
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
     * The role is required. An unknown country code is rejected. The province is a region id from
     * getSupportedCountries(); an unlisted one is saved empty. Poland has no regions.
     *
     * House and flat numbers don't round-trip as separate fields, so the response may split them
     * differently from your request. Read the stored values from the response.
     *
     * A pickup point is for recipients only; `default` is for senders only and makes this the
     * preselected sender.
     *
     * Endpoint: `save_address`
     *
     * Request (omit `id` to create):
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
     *         "postcode": "61-001",
     *         "city": "Poznań",
     *         "country": "PL",
     *         "email": "jan@example.com",
     *         "phone": "500600700",
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
     * A missing `type` gives the same error.
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [country]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"housenumber": ["…"]}}
     * ```
     * Field-level failures. `validation_errors` lists all of them.
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
     * The role is part of the address's identity. If an address is saved under both roles, only the
     * entry for the role you name is removed. The other entry keeps its own id.
     *
     * Deletion is immediate and permanent. Orders already placed with the address are not affected.
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
     * Response (the payload is an empty array, but the SDK still returns `true`):
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
     * Thrown for an unknown id, and for an id that exists only under the other role. Unlike the bank
     * account and pack template deletes, this one reports a miss.
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
