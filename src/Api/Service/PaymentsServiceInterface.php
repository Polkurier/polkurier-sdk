<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Dto\Request\Payment\GetPrepaidHistoryMessage;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Dto\Response\Payment\PaymentInfoMessage;
use Polkurier\Webservice\Dto\Response\Payment\PrepaidHistoryMessage;
use Polkurier\Webservice\Dto\Response\Payment\RegisteredPaymentMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Online payments and the prepaid balance.
 *
 * Paying takes two steps. Register the payment here to get a transaction id and an access token,
 * which together open the payment page for the customer. Then check the outcome with
 * getPaymentInfo().
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 *
 * @api
 */
interface PaymentsServiceInterface
{
    /**
     * Registers an online payment and returns the token needed to complete it.
     *
     * The mode sets what is being paid for and whether the customer chooses the amount. Modes that
     * settle an existing debt charge what is owed, so send an amount only for a prepaid top-up. There
     * it is required and must be greater than zero. The SDK checks this before sending.
     *
     * Invoice and order payments need their list of numbers; the SDK checks it. Numbers with
     * nothing to pay are skipped; if none is left, PARAMETER_INVALID. A balance payment covers every
     * unpaid invoice.
     *
     * An order payment spends the prepaid balance first; if that covers it, the orders are paid at
     * once (state DONE) and there is nothing left to pay online. Other modes move no money when
     * registering.
     *
     * The verification mode fails with UNKNOWN_ERROR; use {@see
     * \Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface::register()}.
     *
     * The transaction id looks the payment up later with getPaymentInfo().
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
     * Nothing to pay among the listed invoices or orders, or no unpaid invoice for a balance
     * payment.
     * ```json
     * {"status": "error", "status_code": 25, "error_code": "PARAMETER_OUT_OF_RANGE",
     *  "response": "Minimalna wartość przedpłaty to …"}
     * ```
     * A top-up below the minimum, which the message names.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Przed dokonaniem płatności należy uzupełnić dane płatnika"}
     * ```
     * Order payments and top-ups need the account's payer details.
     *
     * @param RegisterPaymentMessage $registerPaymentMessage
     *
     * @return RegisteredPaymentMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Payment\RegisteredPaymentMessage}
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
     * The state is NEW, REGISTERED, PENDING or DONE, and only DONE means paid.
     * {@see PaymentInfoMessage} says what each one means.
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
     *         "state": "DONE"
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
     * Unknown transaction, another account's, or registered over two days ago.
     *
     * @param GetPaymentInfoMessage $getPaymentInfoMessage
     *
     * @return PaymentInfoMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Payment\PaymentInfoMessage}
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
     * The description is "Doładowanie konta" for a positive amount, "Pobranie środków z konta"
     * otherwise, sometimes followed by a note.
     *
     * Newest first. Page size defaults to 100 and is capped at 250. As in the invoice list, there is
     * no `totalpages`, so work out the last page from the total and the page size.
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
     *              "description": "Pobranie środków z konta Pobranie środków do zamówienia nr 12345-678"}
     *         ]
     *     }
     * }
     * ```
     *
     * An account that has never used prepaid gets `"totalrows": 0` and `"result": []`.
     *
     * @param GetPrepaidHistoryMessage|null $getPrepaidHistoryMessage null fetches the first page
     *
     * @return PrepaidHistoryMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Payment\PrepaidHistoryMessage}
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
