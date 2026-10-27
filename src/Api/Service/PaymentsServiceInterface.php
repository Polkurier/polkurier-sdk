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
use Polkurier\Webservice\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Request\Payment\GetPrepaidHistoryMessage;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Response\Payment\PaymentInfoMessage;
use Polkurier\Webservice\Response\Payment\PrepaidHistoryMessage;
use Polkurier\Webservice\Response\Payment\RegisteredPaymentMessage;

/**
 * Online payments and the prepaid balance.
 *
 * Paying takes two steps. Register the payment here to get a provider token and send the customer
 * to the provider's hosted page with it. Then check the outcome with getPaymentInfo().
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. With
 * the default configuration the SDK throws these as exceptions.
 */
interface PaymentsServiceInterface
{
    /**
     * Registers an online payment and returns the token needed to complete it.
     *
     * Registering reserves a transaction with the payment provider. The customer pays in the
     * provider's flow, using the returned access token.
     *
     * The mode sets what is being paid for and whether the customer chooses the amount. Modes that
     * settle an existing debt charge what is owed, so send an amount only for a prepaid top-up. There
     * it is required and must be greater than zero. The SDK checks this before sending. The
     * verification mode has a fixed amount, which `get_system_info` reports.
     *
     * Invoice and order payments need their list of numbers; the SDK checks it. Numbers with
     * nothing to pay are skipped; if none is left, PARAMETER_INVALID.
     *
     * An order payment spends the prepaid balance first; if that covers it, the orders are paid at
     * once (state DONE). Other modes move no money when registering.
     *
     * The transaction id is what looks the payment up later.
     *
     * Endpoint: `register_payment`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "register_payment",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"mode": "PREPAID", "amount": 100.00}
     * }
     * ```
     *
     * Request paying invoices (orders go in `orders` the same way, with `"mode": "ORDERS"`):
     * ```json
     * {
     *     "apimethod": "register_payment",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"mode": "INVOICES", "invoices": ["2520/06/2026/POL"]}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "transaction_id": "TR-2026-000123",
     *         "state": "NEW",
     *         "access_token": "a1b2c3d4e5f60718293a4b5c6d7e8f90…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [mode]. Musi zawierać się w zbiorze [BALANCE, INVOICES, ORDERS, PREPAID, VERIFICATION]"}
     * ```
     * A missing `mode` gives the same error.
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Brak faktur do opłacenia"}
     * ```
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Brak zamówień do opłacenia"}
     * ```
     * Nothing to pay among the listed invoices or orders.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Przed dokonaniem płatności należy uzupełnić dane płatnika"}
     * ```
     * Order payments and top-ups need the account's payer details.
     *
     * @param RegisterPaymentMessage $registerPaymentMessage
     *
     * @return RegisteredPaymentMessage payload:
     *         {@see \Polkurier\Webservice\Response\Payment\RegisteredPaymentMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function registerPayment(RegisterPaymentMessage $registerPaymentMessage): RegisteredPaymentMessage;

    /**
     * Reports the current state of a registered payment.
     *
     * This is the reliable result after the customer returns from the provider. The return redirect
     * is not proof of success: it can be forged or reached too early.
     *
     * The hash is the provider's signature over the transaction, for reconciling against a
     * provider-side callback. It isn't meant for display.
     *
     * Endpoint: `get_payment_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_payment_info",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"transaction_id": "TR-2026-000123"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "transaction_id": "TR-2026-000123",
     *         "state": "PAID",
     *         "login_id": 12345,
     *         "hash": "6f1d…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 10, "error_code": "NO_INPUT_OBJECT",
     *  "response": "Brak parametru: [transaction_id]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Błędny numer płatności"}
     * ```
     * Thrown both for an unknown transaction and for one that belongs to another account.
     *
     * @param GetPaymentInfoMessage $getPaymentInfoMessage
     *
     * @return PaymentInfoMessage payload:
     *         {@see \Polkurier\Webservice\Response\Payment\PaymentInfoMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPaymentInfo(GetPaymentInfoMessage $getPaymentInfoMessage): PaymentInfoMessage;

    /**
     * Lists movements on the prepaid balance.
     *
     * Amounts are signed: negative for spending, positive for a top-up. The running balance is not
     * included. Read the current balance from `get_account_info`.
     *
     * Paging works like the invoice list. There is no `totalpages` either, so work out the last page
     * from the total and the page size.
     *
     * Endpoint: `get_prepaid_history`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_prepaid_history",
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
     *         "totalrows": 2,
     *         "currentpage": 1,
     *         "pagesize": 100,
     *         "result": [
     *             {"date": "2026-09-01T12:00:00+02:00", "amount": 100.00,
     *              "description": "Doładowanie konta"},
     *             {"date": "2026-09-02T09:15:00+02:00", "amount": -23.99,
     *              "description": "Zlecenie 12345-678"}
     *         ]
     *     }
     * }
     * ```
     *
     * An account that has never used prepaid gets a zeroed envelope with `"result": []`.
     *
     * @param GetPrepaidHistoryMessage|null $getPrepaidHistoryMessage null fetches the first page
     *
     * @return PrepaidHistoryMessage payload:
     *         {@see \Polkurier\Webservice\Response\Payment\PrepaidHistoryMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPrepaidHistory(
        ?GetPrepaidHistoryMessage $getPrepaidHistoryMessage = null
    ): PrepaidHistoryMessage;
}
