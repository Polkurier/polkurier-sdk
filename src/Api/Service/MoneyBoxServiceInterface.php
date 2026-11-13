<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\MoneyBox\CalculateMoneyBoxPayoutFeeMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\CancelMoneyBoxPayoutMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\GetMoneyBoxPayoutsMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxFeeMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

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
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 */
interface MoneyBoxServiceInterface
{
    /**
     * Returns the account's payout settings.
     *
     * Read this before offering a payout. It gives the account's payout method, the fee payment
     * options it can choose from, and the destination details on file. A destination with nothing
     * saved comes back blank.
     *
     * Endpoint: `get_money_box_config`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_money_box_config",
     *     "authorization": {"login": "12345", "token": "…"}
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
     *         {@see \Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxConfigMessage}
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
     * Call it even when the fee looks predictable. Only this response reports the free payout
     * counters.
     *
     * The fee can have several components, which `fees` lists: PAYOUT_FEE once the month's free
     * payouts are used up, and POSTAL_TRANSFER_FEE for a postal money order. Without a payout type,
     * the quote has no postal money order fee.
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
     *        for everything available, without a payout type
     *
     * @return MoneyBoxFeeMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxFeeMessage}
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
     * Moves money and is not idempotent. After a timeout, check getPayouts() before retrying.
     *
     * Fails unless the money box is enabled and payouts aren't blocked by overdue invoices or an
     * account or payout lock.
     *
     * The payout type, ACCOUNT by default, decides the destination fields. Nothing is filled in
     * from getConfig():
     *
     * - bank transfer: an account number from the saved bank accounts, otherwise FORBIDDEN;
     * - postal money order: the postal address.
     *
     * Without a fee payment type, the fee is deducted from the money box, or added to an invoice
     * where INVOICE is the only type getConfig() offers.
     *
     * Only a postal money order to an address other than the saved default needs a confirmation,
     * one of:
     *
     * - the security PIN, while `pin_locks.COD_MONEY_BOX` is on;
     * - otherwise, a two-factor token registered under MONEY_BOX_PAYOUT, with its code.
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
     *         "bank_account_number": "34102010260000000000000001"
     *     }
     * }
     * ```
     *
     * Request for a money order to another address, with two-factor:
     * ```json
     * {
     *     "apimethod": "create_money_box_payout",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "payout_type": "POSTAL",
     *         "fee_payment_type": "INVOICE",
     *         "person": "Jan Kowalski",
     *         "street": "Długa 12",
     *         "postcode": "61-001",
     *         "city": "Poznań",
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
     * Payouts are blocked.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Nieznany numer konta bankowego. Zdefiniuj numer konta w ustawieniach."}
     * ```
     * The account number is missing or not one of the saved bank accounts.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Brak środków aby uregulować opłatę manipulacyjną za dyspozycję wypłaty ze skarbonki"}
     * ```
     * Not enough funds to pay the handling fee.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "Podany PIN jest nieprawidłowy"}
     * ```
     * Wrong security PIN.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Brak środków do wypłaty [cods]", "validation_errors": {"cods": ["Brak środków do wypłaty"]}}
     * ```
     * Nothing to pay out among the selected orders.
     *
     * @param CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutMessage}
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
     * You can only cancel while the payout's `is_cancellable` flag is true. It turns false once any
     * part of the payout is on its way or paid, and once its fee is paid other than on an invoice. A
     * cancel attempt then fails with the reason. Check the flag first.
     *
     * A payout that belongs to another account gives NOT_FOUND.
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
     * Response: the payout as it was before the cancel. The payout is removed, and getPayouts() does
     * not list it afterwards.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "…"}
     * ```
     * Thrown when `is_cancellable` is false. The message gives the reason.
     *
     * @param CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutMessage}
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
     * Unlike the invoice and prepaid listings, this envelope includes `totalpages`. Page size is
     * capped at 100 and is 1 when left out.
     *
     * A payout splits its `amount` into the part still to pay, the part paid and any compensation
     * withheld against debts, so less than `amount` may be paid out. `status` is PENDING until
     * nothing is left to pay, then PAID. `fee_payment_status` is PAID from the start for a fee added
     * to an invoice and for a free payout.
     *
     * Endpoint: `get_money_box_payouts`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_money_box_payouts",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"pagesize": 100, "page": 1}
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
     *                 "fee_payment_status": "PAID",
     *                 "status": "PENDING",
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
     * With a payout number, the API returns that payout alone, or `[]` if the number is unknown.
     * The SDK returns a one-row or empty page.
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * @param GetMoneyBoxPayoutsMessage|null $getMoneyBoxPayoutsMessage null lists the first page,
     *        of one payout
     *
     * @return MoneyBoxPayoutListMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPayouts(?GetMoneyBoxPayoutsMessage $getMoneyBoxPayoutsMessage = null): MoneyBoxPayoutListMessage;
}
