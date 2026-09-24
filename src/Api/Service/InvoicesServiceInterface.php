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
use Polkurier\Webservice\Request\Invoice\GetInvoiceDocumentMessage;
use Polkurier\Webservice\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceListMessage;

/**
 * Invoices issued to the account, and the documents behind them.
 *
 * Invoices and their corrections are listed separately, so reconciling what an account owes takes
 * both calls.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface InvoicesServiceInterface
{
    /**
     * Lists invoices, one page at a time.
     *
     * The document kind is mandatory — invoices and corrections are separate lists rather than a
     * filter on one list.
     *
     * The paging envelope here is one field shorter than the order list's: there is no
     * `totalpages`, so the last page has to be worked out from the total and the page size.
     *
     * The unpaid value is the useful one for reconciliation: it is what remains after part
     * payments and compensations, and reaches zero when the invoice is settled.
     *
     * Endpoint: `get_invoices`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_invoices",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"type": "INVOICE", "pagesize": 100, "page": 1}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "totalrows": 24,
     *         "currentpage": 1,
     *         "pagesize": 100,
     *         "result": [
     *             {
     *                 "number": "2520/06/2026/POL",
     *                 "value": 611.31,
     *                 "unpaid_value": 0,
     *                 "date": "2026-06-30",
     *                 "payment_deadline": "2026-07-07"
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * An account with no invoices of that kind gets a zeroed envelope with `"result": []`.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [type]. Musi zawierać się w zbiorze [INVOICE, INVOICE_CORRECTION]"}
     * ```
     * Also what a missing `type` gives.
     *
     * @param GetInvoicesMessage $getInvoicesMessage
     *
     * @return InvoiceListMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceListMessage}
     */
    public function getInvoices(GetInvoicesMessage $getInvoicesMessage): InvoiceListMessage;

    /**
     * Downloads one invoice as a PDF.
     *
     * Addressed by invoice number rather than by an opaque id, so the number from getInvoices() —
     * slashes included — goes in verbatim.
     *
     * The response carries a suggested filename alongside the document, which is safe to use in a
     * download header as-is.
     *
     * Endpoint: `get_invoice_pdf`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_invoice_pdf",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"number": "2520/06/2026/POL"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "faktura_nr": "2520/06/2026/POL",
     *         "filename": "2520_06_2026_POL.pdf",
     *         "filetype": "application/pdf",
     *         "file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nie znaleziono obiektu"}
     * ```
     * Raised for an unknown number and for one belonging to another account alike.
     *
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage}
     */
    public function getInvoicePdf(GetInvoiceDocumentMessage $getInvoiceDocumentMessage): InvoiceDocumentMessage;

    /**
     * Downloads the itemised summary behind one invoice.
     *
     * Where the PDF is the invoice itself, this is the breakdown of the shipments it covers —
     * useful for reconciling a monthly invoice against individual orders.
     *
     * Addressed the same way, by invoice number.
     *
     * Endpoint: `get_invoice_summary_list`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_invoice_summary_list",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"number": "2520/06/2026/POL", "format": "pdf"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "faktura_nr": "2520/06/2026/POL",
     *         "filename": "zestawienie_2520_06_2026.pdf",
     *         "type": "pdf",
     *         "file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nie znaleziono obiektu"}
     * ```
     * Also what a missing `number` gives, since the lookup simply finds nothing.
     *
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage}
     */
    public function getInvoiceSummaryList(
        GetInvoiceDocumentMessage $getInvoiceDocumentMessage
    ): InvoiceDocumentMessage;
}
