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
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;
use Polkurier\Webservice\Response\PackTemplate;

/**
 * Saved parcel templates: reusable dimensions, weight and service selections.
 *
 * Templates exist to save re-entering the same parcel; they are not referenced by an order. Read
 * one and copy its values into the order payload.
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
interface PackTemplatesServiceInterface
{
    /**
     * Lists the account's parcel templates.
     *
     * Endpoint: `get_pack_templates`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_pack_templates",
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
     *             "id": "aB3",
     *             "name": "Karton A",
     *             "shipmenttype": "box",
     *             "type": "ST",
     *             "length": 30, "width": 20, "height": 15, "weight": 2.5,
     *             "COD": 0, "insurance": 0,
     *             "description": "",
     *             "courier": "UPS",
     *             "services": ["SMS_NOTIFICATION_RECIPIENT"],
     *             "default": false
     *         }
     *     ]
     * }
     * ```
     *
     * @return PackTemplate[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getPackTemplates(): array;

    /**
     * Creates a template, or updates the one its id names.
     *
     * Values are normalised rather than rejected: dimensions come back as whole numbers, weight and
     * monetary amounts to two decimals, an unrecognised parcel type as an unknown marker, and
     * unrecognised service codes are not kept — so a template can come back with fewer services than
     * were sent. Read the response rather than assuming it echoes the request.
     *
     * The name, however, is required, and the shipment type must be a known one.
     *
     * Endpoint: `save_pack_template`
     *
     * Request; omit `id` to create:
     * ```json
     * {
     *     "apimethod": "save_pack_template",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "id": "aB3",
     *         "name": "Karton A",
     *         "shipmenttype": "box",
     *         "type": "ST",
     *         "length": 30, "width": 20, "height": 15, "weight": 2.5,
     *         "COD": 0, "insurance": 0,
     *         "description": "",
     *         "courier": "UPS",
     *         "services": ["SMS_NOTIFICATION_RECIPIENT"]
     *     }
     * }
     * ```
     *
     * Response: the stored template, in the same shape getPackTemplates() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Pole nazwa nie może być puste [name]",
     *  "validation_errors": {
     *      "name": ["Pole nazwa nie może być puste"],
     *      "shipmenttype": ["Błędny typ przesyłki"]
     *  }}
     * ```
     * The message names only the first failure; `validation_errors` carries them all, keyed by the
     * API's own field names.
     *
     * @param SaveMessage $saveMessage
     *
     * @return PackTemplate as stored
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePackTemplates(SaveMessage $saveMessage): PackTemplate;

    /**
     * Deletes a template.
     *
     * Templates are only a convenience for composing orders, so removing one has no effect on orders
     * already placed from it.
     *
     * As with bank accounts, deleting an unknown id is a silent success rather than an error.
     *
     * Endpoint: `delete_pack_template`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "delete_pack_template",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "aB3"}
     * }
     * ```
     *
     * Response, both for a real deletion and for an unknown id:
     * ```json
     * {"status": "success", "response": []}
     * ```
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
    public function deletePackTemplates(DeleteMessage $deleteMessage): bool;
}
