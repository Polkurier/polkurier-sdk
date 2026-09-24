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
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Response\System\CouponValidationMessage;
use Polkurier\Webservice\Response\System\CourierMessage;
use Polkurier\Webservice\Response\System\SystemInfoMessage;

/**
 * Service-wide configuration, carrier notices and coupon checks.
 *
 * None of this is specific to one order or one resource; it is the context a client needs in order
 * to present the service correctly.
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
interface SystemServiceInterface
{
    /**
     * Service limits a client should validate against.
     *
     * Everything here is policy rather than contract — PIN lengths, two-factor code length and resend
     * window, attachment size caps, the minimum coupon length, the amount of the bank verification
     * transfer, and how long a customer has to accept revised terms. It can change without an API
     * version bump, so read it at start-up rather than hard-coding the numbers.
     *
     * Carries no account data, so it is safe to cache and share across a process.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\System\SystemInfoMessage}
     */
    public function getSystemInfo(): SystemInfoMessage;

    /**
     * Current notices about carriers — delays, suspensions, general information.
     *
     * Two sources arrive merged: operational alerts, which mean orders placed now will be affected, and
     * informational messages. The type distinguishes them and the severity is a presentation hint only.
     *
     * Worth showing before a customer commits to a carrier. The text is Polish and ready to display;
     * there is no machine-readable reason code behind it.
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
     *         }
     *     ]
     * }
     * ```
     *
     * With nothing to report the response is `[]`.
     *
     * @return CourierMessage[] payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\System\CourierMessage}[]
     */
    public function getCouriersMessages(): array;

    /**
     * Checks a discount coupon, optionally against a specific order.
     *
     * The code is normalised before lookup — whitespace and symbols are stripped — so what comes back
     * may differ from what was sent, and it is the normalised form that should be quoted when placing
     * the order.
     *
     * Two independent verdicts. `valid` says the coupon exists and this account may use it.
     * `valid_for_order` additionally checks it against the carrier, contents and sender region of the
     * order supplied alongside; with no order supplied it is always false, which means "not checked"
     * rather than "not applicable".
     *
     * The order context is all-or-nothing: carrier, shipment type and parcels must be sent together. The
     * SDK rejects a partial set in the message constructor rather than spending a round trip.
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
     * Response, a code that does not exist — a success, not an error, with every descriptive field null
     * and `couriers` null rather than an empty list:
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
     * What a partial order context gives, naming whichever of the three is missing.
     *
     * @param ValidateCouponMessage $validateCouponMessage
     *
     * @return CouponValidationMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\System\CouponValidationMessage}
     */
    public function validateCouponCode(ValidateCouponMessage $validateCouponMessage): CouponValidationMessage;
}
