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
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;
use Polkurier\Webservice\Response\PackTemplate;

/**
 * Saved parcel templates: reusable dimensions, weight and service selections.
 *
 * An order can't reference a template. To use one, read it and copy its values into the order
 * payload.
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
interface PackTemplatesServiceInterface
{
    /**
     * Lists the account's parcel templates, in the order the account's template sorting in the panel
     * sets.
     *
     * Endpoint: `get_pack_templates`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_pack_templates",
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
     *             "id": "aB3",
     *             "name": "Karton A",
     *             "shipmenttype": "box",
     *             "type": "ST",
     *             "length": 30, "width": 20, "height": 15, "weight": 2.5,
     *             "COD": 0, "insurance": 0,
     *             "description": "",
     *             "courier": "UPS",
     *             "services": ["SMS_NOTIFICATION_RECIPIENT"]
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
     * Creates a template, or updates the one its id names. An id that names none of the account's
     * templates creates a new one.
     *
     * Dimensions are stored as whole centimetres, the weight and amounts rounded to 2 decimals. Use
     * parcel types from {@see \Polkurier\Webservice\Enum\Order\PackTypeEnum} and
     * service codes from {@see \Polkurier\Webservice\Enum\Order\AdditionalServiceEnum}. Read the
     * stored values from the response.
     *
     * The name is required. The SDK checks this before sending.
     *
     * Endpoint: `save_pack_template`
     *
     * Request (omit `id` to create):
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
     *      "shipmenttype": ["Błędny rodzaj przesyłki"],
     *      "type": ["Błędny typ przesyłki"]
     *  }}
     * ```
     * The message names only the first failure. `validation_errors` has all of them, keyed by the
     * API's field names.
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
     * Deleting an unknown id also succeeds.
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
