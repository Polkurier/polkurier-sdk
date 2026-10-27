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
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Response\System\CouponValidationMessage;
use Polkurier\Webservice\Response\System\CourierMessage;
use Polkurier\Webservice\Response\System\SystemInfoMessage;

/**
 * Service-wide configuration, carrier notices and coupon checks.
 *
 * None of this is tied to a particular order or resource.
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
interface SystemServiceInterface
{
    /**
     * Returns the service limits to validate input against.
     *
     * This covers PIN lengths, the two-factor code length and resend window, attachment size limits,
     * the minimum coupon length, the bank verification transfer amount, and how long a customer has
     * to accept revised terms. These values can change without an API version bump.
     *
     * `features` holds feature flags keyed by name.
     *
     * The response has no account data, so you can cache it and share it across a process.
     *
     * Endpoint: `get_system_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_system_info",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "verification_transfer_amount_pln": 2,
     *         "regulations_acceptance_period": 14,
     *         "coupons": {"min_length": 4},
     *         "tickets": {"max_file_size": 10485760, "max_image_size": 5242880, "max_files_count": 5},
     *         "security": {
     *             "user_pin_min_length": 4,
     *             "user_pin_max_length": 8,
     *             "two_factor_code_length": 6,
     *             "two_factor_resend_throttle": 60
     *         }
     *     }
     * }
     * ```
     *
     * @return SystemInfoMessage payload:
     *         {@see \Polkurier\Webservice\Response\System\SystemInfoMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getSystemInfo(): SystemInfoMessage;

    /**
     * Returns current carrier notices: delays, suspensions and general information.
     *
     * Each notice has a `type`. Alerts mean orders placed now will be affected. The others are
     * informational. `severity` is only a display hint.
     *
     * Show these before a customer picks a carrier. The text is in Polish and ready to display. There
     * is no machine-readable reason code.
     *
     * Endpoint: `get_couriers_messages`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_couriers_messages",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
     * }
     * ```
     *
     * `ALERT` notices have `hide_timeout`; `MESSAGE` notices have `title` and `priority` instead.
     * Alerts come first, then messages by descending `priority`.
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "courier": "FEDEX",
     *             "message": "Uwaga! U przewoźnika występują chwilowe utrudnienia…",
     *             "type": "ALERT",
     *             "severity": "WARNING",
     *             "hide_timeout": 4
     *         },
     *         {
     *             "courier": "FEDEX",
     *             "title": "Płatność kartą za pobranie",
     *             "message": "<p>Uwaga! Przewoźnik nalicza opłatę w sytuacji, gdy odbiorca opłaci pobranie kartą…</p>",
     *             "type": "MESSAGE",
     *             "severity": "WARNING",
     *             "priority": 5
     *         }
     *     ]
     * }
     * ```
     *
     * With nothing to report the response is `[]`.
     *
     * @return CourierMessage[] payload:
     *         {@see \Polkurier\Webservice\Response\System\CourierMessage}[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getCouriersMessages(): array;

    /**
     * Checks a discount coupon, optionally against a specific order.
     *
     * The returned `code` may differ from what you sent. Use the returned form when you place the
     * order.
     *
     * The response has 2 separate verdicts. `valid` means the coupon exists and this account may use
     * it. `valid_for_order` also checks it against the carrier, contents and sender region of the
     * order you pass. Without an order it is always false, meaning "not checked".
     *
     * Send the carrier, shipment type and parcels together or not at all. With payload validation
     * on (the default), the SDK throws {@see \Polkurier\Webservice\Exception\ValidationException}
     * for a partial set before sending the
     * request, listing every missing field.
     *
     * Endpoint: `validate_coupon_code`
     *
     * Request, code only:
     * ```json
     * {
     *     "apimethod": "validate_coupon_code",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"coupon_code": "SPRING10"}
     * }
     * ```
     *
     * Request, checked against an order:
     * ```json
     * {
     *     "apimethod": "validate_coupon_code",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "coupon_code": "SPRING10",
     *         "courier": "UPS",
     *         "shipmenttype": "box",
     *         "packs": [{"length": 30, "width": 20, "height": 15, "weight": 2.5, "type": "ST"}],
     *         "sender": {"postcode": "61-001", "city": "Poznań", "country": "PL"}
     *     }
     * }
     * ```
     *
     * Response, a valid coupon:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "code": "SPRING10",
     *         "valid": true,
     *         "valid_for_order": true,
     *         "type": "PERCENT",
     *         "value": 10,
     *         "value_used": 0,
     *         "usage_type": "MULTIPLE",
     *         "couriers": ["UPS"],
     *         "valid_to": "2026-12-31T23:59:59+01:00",
     *         "description": "Rabat 10% na przesyłki UPS"
     *     }
     * }
     * ```
     *
     * Response for a code that doesn't exist. This is a success, not an error. Every descriptive field
     * is null, and `couriers` is null instead of an empty list:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "code": "NOPE123",
     *         "valid": false,
     *         "valid_for_order": false,
     *         "type": null, "value": null, "value_used": null, "usage_type": null,
     *         "couriers": null, "valid_to": null,
     *         "description": ""
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [coupon_code]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [shipmenttype]"}
     * ```
     * The API's answer to a partial order context (with payload validation off). It names one of
     * the missing fields.
     *
     * @param ValidateCouponMessage $validateCouponMessage
     *
     * @return CouponValidationMessage payload:
     *         {@see \Polkurier\Webservice\Response\System\CouponValidationMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function validateCouponCode(ValidateCouponMessage $validateCouponMessage): CouponValidationMessage;
}
