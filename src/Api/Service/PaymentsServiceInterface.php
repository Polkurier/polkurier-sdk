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
use Polkurier\Webservice\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Request\Payment\GetPrepaidHistoryMessage;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Response\Payment\PaymentInfoMessage;
use Polkurier\Webservice\Response\Payment\PrepaidHistoryMessage;
use Polkurier\Webservice\Response\Payment\RegisteredPaymentMessage;

/**
 * Online payments and the prepaid balance.
 *
 * Payment is a two-step flow: register here to obtain a provider token, send the customer through
 * the provider's hosted page with it, then confirm the outcome with getPaymentInfo(). The API is
 * never told about the payment by this SDK — the provider reports it.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface PaymentsServiceInterface
{
    /**
     * Registers an online payment and returns the token needed to complete it.
     *
     * Registering does not move any money; it reserves a transaction with the payment provider.
     * The customer must then be sent through the provider's flow using the returned access token,
     * and the payment only settles when they finish there.
     *
     * The mode decides what is being paid for and whether the amount is the customer's to choose.
     * For modes that settle an existing debt the sum is determined by what is owed and any amount
     * sent is ignored; for a prepaid top-up the amount is the point of the call. The verification
     * mode has a fixed amount, reported by `get_system_info`.
     *
     * Keep the transaction id: it is the only handle on the payment afterwards.
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
     * Also what a missing `mode` gives.
     *
     * @param RegisterPaymentMessage $registerPaymentMessage
     *
     * @return RegisteredPaymentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Payment\RegisteredPaymentMessage}
     */
    public function registerPayment(RegisterPaymentMessage $registerPaymentMessage): RegisteredPaymentMessage;

    /**
     * Reports where a registered payment has got to.
     *
     * The authoritative check after the customer returns from the provider — do not infer success
     * from the redirect they came back on, since that can be forged or simply reached early.
     *
     * The hash is the provider's signature over the transaction and exists for reconciliation
     * against a provider-side callback rather than for display.
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
     * Raised for an unknown transaction and for one belonging to another account alike.
     *
     * @param GetPaymentInfoMessage $getPaymentInfoMessage
     *
     * @return PaymentInfoMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Payment\PaymentInfoMessage}
     */
    public function getPaymentInfo(GetPaymentInfoMessage $getPaymentInfoMessage): PaymentInfoMessage;

    /**
     * Lists movements on the prepaid balance.
     *
     * Amounts are signed: negative for spending, positive for a top-up. The running balance is not
     * reported here — read the current one from `get_account_info`.
     *
     * Paged like the invoice list, and with the same missing `totalpages`, so the last page has to
     * be worked out from the total and the page size.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Payment\PrepaidHistoryMessage}
     */
    public function getPrepaidHistory(
        ?GetPrepaidHistoryMessage $getPrepaidHistoryMessage = null
    ): PrepaidHistoryMessage;
}
