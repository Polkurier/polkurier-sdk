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
use Polkurier\Webservice\Request\MoneyBox\CalculateMoneyBoxPayoutFeeMessage;
use Polkurier\Webservice\Request\MoneyBox\CancelMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\GetMoneyBoxPayoutsMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxFeeMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage;

/**
 * The money box: cash-on-delivery funds held on the account, and withdrawing them.
 *
 * Collected cash-on-delivery money builds up here instead of being transferred per shipment. The
 * customer withdraws it in batches. `get_account_info` reports 3 balances: held, available to
 * withdraw now, and already committed to a payout.
 *
 * The money box can be switched off per account. When it is, every method here fails with
 * forbidden. The usual order is getConfig() to see how the account is set up, calculatePayoutFee()
 * for a quote, then createPayout().
 *
 * createPayout() moves money and is not idempotent. Read its notes before you add a retry.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. With
 * the default configuration the SDK throws these as exceptions.
 */
interface MoneyBoxServiceInterface
{
    /**
     * Returns the account's payout settings.
     *
     * Read this before offering a payout. It gives the account's payout method, the fee payment
     * options it can choose from, and the destination details on file. Only the destination fields
     * for the account's payout type are filled in. The others are blank.
     *
     * Endpoint: `get_money_box_config`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_money_box_config",
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
     *         "payout_type": "ACCOUNT",
     *         "fee_payment": "INVOICE",
     *         "fee_payments_types_available": ["INVOICE"],
     *         "bank_account_number": "34102010260000000000000001",
     *         "company": "", "person": "", "street": "", "postcode": "", "city": ""
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "Brak uprawnień"}
     * ```
     * Thrown when the money box is not enabled for this account.
     *
     * @return MoneyBoxConfigMessage payload:
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxConfigMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getConfig(): MoneyBoxConfigMessage;

    /**
     * Quotes what a payout would cost, without requesting one.
     *
     * Nothing is created or reserved. If you send the same orders and payout type you plan to use,
     * the quote is the fee that will apply.
     *
     * Call it even when the fee looks predictable. An account with free payouts left pays nothing,
     * and only this response reports the free payout counters.
     *
     * The fee can have several components. `fees` lists them.
     *
     * Endpoint: `calculate_money_box_payout_fee`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "calculate_money_box_payout_fee",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"orders": ["12345-678"], "payout_type": "ACCOUNT"}
     * }
     * ```
     * Leave out `orders` to quote a payout of everything available.
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "fee": 2.46,
     *         "fee_nett": 2,
     *         "fee_vat_rate": 23,
     *         "free_payouts_left": 0,
     *         "free_payouts_total": 0,
     *         "fees": [
     *             {
     *                 "type": "PAYOUT_FEE",
     *                 "name": "Opłata za dyspozycję wypłaty",
     *                 "description": "",
     *                 "fee": 2.46,
     *                 "fee_nett": 2,
     *                 "vat_rate": 23
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * @param CalculateMoneyBoxPayoutFeeMessage|null $calculateMoneyBoxPayoutFeeMessage null quotes
     *        for everything available, using the account's configured payout type
     *
     * @return MoneyBoxFeeMessage payload:
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxFeeMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function calculatePayoutFee(
        ?CalculateMoneyBoxPayoutFeeMessage $calculateMoneyBoxPayoutFeeMessage = null
    ): MoneyBoxFeeMessage;

    /**
     * Requests a payout from the money box balance.
     *
     * This moves money. It is not idempotent: each accepted call creates a separate payout request.
     * Treat a timeout as an unknown outcome, not a failure, and check getPayouts() before retrying.
     *
     * The call fails unless the money box is enabled for the account, the account has no overdue
     * invoices, and the confirmations below are provided. Unlike the bank account endpoints, where
     * you send either the PIN or two-factor, here both can apply:
     *
     * - The security PIN is required while the money box settings are PIN-locked.
     *   `get_account_info` reports this as `pin_locks.COD_MONEY_BOX`.
     * - A verified two-factor token registered under MONEY_BOX_PAYOUT is also required.
     *
     * The payout type decides which destination fields apply. A bank transfer needs the account
     * number. A money order needs the postal address. Fields you leave out default to the values
     * getConfig() returns.
     *
     * Endpoint: `create_money_box_payout`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "create_money_box_payout",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "orders": ["12345-678"],
     *         "payout_type": "ACCOUNT",
     *         "fee_payment_type": "INVOICE",
     *         "bank_account_number": "34102010260000000000000001",
     *         "security_pin": "1234",
     *         "two_factor_token": "8f3c1e2a-…",
     *         "two_factor_form_id": "…",
     *         "two_factor_code": "123456"
     *     }
     * }
     * ```
     *
     * Response: the created payout, in the same shape getPayouts() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "Brak uprawnień"}
     * ```
     * The money box is not enabled for this account.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Nie można zlecić wypłaty, ze względu na zaległości w płatnościach faktur"}
     * ```
     * Payouts are blocked while invoices are overdue.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"bank_account_number": ["…"]}}
     * ```
     *
     * @param CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage payload:
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createPayout(CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage): MoneyBoxPayoutMessage;

    /**
     * Cancels a payout that has not yet been sent.
     *
     * You can only cancel while the payout's `is_cancellable` flag is true. Once it is in progress it
     * can't be recalled, and a cancel attempt fails with the reason. Check the flag first.
     *
     * A payout that belongs to another account gives not found, not forbidden.
     *
     * Endpoint: `cancel_money_box_payout`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "cancel_money_box_payout",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"payout_number": "WP/2026/000123"}
     * }
     * ```
     *
     * Response: the payout with its updated status.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "…"}
     * ```
     * Thrown when the payout is too far along to cancel. The message gives the reason.
     *
     * @param CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage payload:
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function cancelPayout(CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage): MoneyBoxPayoutMessage;

    /**
     * Lists payout requests, one page at a time.
     *
     * Unlike the invoice and prepaid listings, this envelope includes `totalpages`.
     *
     * Each payout splits the requested amount into the part still to pay, the part paid, and any
     * amount withheld as compensation against outstanding debts. A payout can therefore complete
     * with less paid out than its `amount`.
     *
     * Endpoint: `get_money_box_payouts`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_money_box_payouts",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"payout_number": "", "search_query": "", "pagesize": 100, "page": 1}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "totalrows": 1,
     *         "totalpages": 1,
     *         "currentpage": 1,
     *         "pagesize": 100,
     *         "result": [
     *             {
     *                 "number": "WP/2026/000123",
     *                 "date": "2026-09-01T12:00:00+02:00",
     *                 "request_type": "MANUAL",
     *                 "payout_type": "ACCOUNT",
     *                 "bank_account_number": "34102010260000000000000001",
     *                 "company": "", "person": "", "street": "", "postcode": "", "city": "",
     *                 "fee": 2.46, "fee_nett": 2, "fee_vat_rate": 23,
     *                 "fee_payment_type": "INVOICE",
     *                 "fee_payment_status": "UNPAID",
     *                 "status": "NEW",
     *                 "amount": 420.00,
     *                 "to_pay_amount": 420.00,
     *                 "paid_amount": 0,
     *                 "compensation_amount": 0,
     *                 "items_count": 1,
     *                 "is_cancellable": true
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * @param GetMoneyBoxPayoutsMessage|null $getMoneyBoxPayoutsMessage null lists the first page
     *
     * @return MoneyBoxPayoutListMessage payload:
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPayouts(?GetMoneyBoxPayoutsMessage $getMoneyBoxPayoutsMessage = null): MoneyBoxPayoutListMessage;
}
