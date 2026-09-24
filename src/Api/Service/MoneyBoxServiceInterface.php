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
 * Collected cash-on-delivery money accumulates here rather than being transferred per shipment,
 * and the customer withdraws it in batches. `get_account_info` reports the three balances — held,
 * withdrawable now, and already committed to a payout.
 *
 * The whole feature can be switched off per account; every method here answers forbidden when it
 * is. The natural order is getConfig() to see how the account is set up, calculatePayoutFee() to
 * quote, then createPayout().
 *
 * createPayout() moves money and is not idempotent — read its notes before wiring a retry.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface MoneyBoxServiceInterface
{
    /**
     * The account's standing payout arrangement.
     *
     * Worth reading before offering a payout: it gives the delivery method the account is set up
     * for, the fee settlement options it may choose from, and the destination details already on
     * file. The destination fields are filled in for whichever payout type applies and blank for
     * the other.
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
     * Raised when the money box is not enabled for this account.
     *
     * @return MoneyBoxConfigMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxConfigMessage}
     */
    public function getConfig(): MoneyBoxConfigMessage;

    /**
     * Quotes what a payout would cost, without requesting one.
     *
     * Nothing is created or reserved. Send the same orders and payout type you intend to use and
     * the quote is the fee that will actually apply.
     *
     * Worth calling even when the fee looks predictable, because of the free payout allowance: an
     * account with free payouts remaining is charged nothing, and the counters here are the only
     * place that is reported.
     *
     * The fee can combine several components, so the breakdown is itemised.
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
     * Omitting `orders` quotes for a payout of everything available.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxFeeMessage}
     */
    public function calculatePayoutFee(
        ?CalculateMoneyBoxPayoutFeeMessage $calculateMoneyBoxPayoutFeeMessage = null
    ): MoneyBoxFeeMessage;

    /**
     * Requests a payout from the money box balance.
     *
     * This moves money. It is not idempotent — each accepted call creates a separate payout
     * request — so treat a timeout as unknown rather than failed and reconcile with getPayouts()
     * before retrying.
     *
     * Three gates fire before anything is created: the money box must be enabled for the account,
     * the account must not be behind on invoice payments, and the two confirmations below must be
     * satisfied. Unlike the bank account endpoints, where the PIN and two-factor are alternatives,
     * here they can both apply:
     *
     * - The security PIN is required while the account has PIN-locked its money box settings,
     *   which `get_account_info` reports as `pin_locks.COD_MONEY_BOX`.
     * - A verified two-factor token registered under MONEY_BOX_PAYOUT is required as well.
     *
     * Which destination fields matter depends on the payout type: a bank transfer uses the account
     * number and ignores the postal address, a money order the reverse. Omitting them falls back
     * to what is on file.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage}
     */
    public function createPayout(CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage): MoneyBoxPayoutMessage;

    /**
     * Cancels a payout that has not yet been sent.
     *
     * Only possible while the payout is still cancellable, which its `is_cancellable` flag
     * reports — once it is in flight it cannot be recalled, and attempting it is refused with the
     * reason. Check the flag rather than attempting and handling the failure.
     *
     * A payout belonging to another account is reported as not found rather than forbidden.
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
     * Response: the payout as it now stands, with its status updated.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "…"}
     * ```
     * Raised when the payout has progressed too far to cancel; the message carries the reason.
     *
     * @param CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage}
     */
    public function cancelPayout(CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage): MoneyBoxPayoutMessage;

    /**
     * Lists payout requests, one page at a time.
     *
     * This envelope does report `totalpages`, unlike the invoice and prepaid listings.
     *
     * Each payout separates what was requested from what has moved: the total, the part still to
     * pay, the part paid, and anything withheld as compensation against outstanding debts — so a
     * payout can complete having paid out less than it was for.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutListMessage}
     */
    public function getPayouts(?GetMoneyBoxPayoutsMessage $getMoneyBoxPayoutsMessage = null): MoneyBoxPayoutListMessage;
}
