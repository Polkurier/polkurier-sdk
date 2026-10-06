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
use Polkurier\Webservice\Request\Invoice\GetInvoiceSummaryListMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceListMessage;
use Polkurier\Webservice\Response\Invoice\InvoiceSummaryListMessage;

/**
 * Invoices issued to the account, and the documents behind them.
 *
 * Invoices and corrections are listed separately. To reconcile what an account owes, fetch both.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. With
 * the default configuration the SDK throws these as exceptions.
 */
interface InvoicesServiceInterface
{
    /**
     * Lists invoices, one page at a time.
     *
     * The document type is required. Invoices and corrections are separate lists.
     *
     * Unlike the order list, the paging envelope has no `totalpages`. Work out the last page from
     * the total and the page size.
     *
     * For reconciliation, use `unpaid_value`. It is the amount left after part payments and
     * compensations, and it drops to zero once the invoice is settled.
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
     * A missing `type` gives the same error.
     *
     * @param GetInvoicesMessage $getInvoicesMessage
     *
     * @return InvoiceListMessage payload:
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoices(GetInvoicesMessage $getInvoicesMessage): InvoiceListMessage;

    /**
     * Downloads one invoice as a PDF.
     *
     * Pass the invoice number from getInvoices() as returned, slashes included.
     *
     * The response includes a suggested filename, which you can use in a download header as is.
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
     * Thrown both for an unknown number and for one that belongs to another account.
     *
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage payload:
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceDocumentMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoicePdf(GetInvoiceDocumentMessage $getInvoiceDocumentMessage): InvoiceDocumentMessage;

    /**
     * Downloads the itemised summary behind one invoice.
     *
     * This is the breakdown of the shipments an invoice covers. Use it to reconcile a monthly
     * invoice against individual orders.
     *
     * Pass the invoice number, as for getInvoicePdf(), and the format: PDF to read or print, CSV to
     * import. The format is required and goes out in capitals. The SDK sends PDF unless you choose
     * otherwise.
     *
     * Endpoint: `get_invoice_summary_list`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_invoice_summary_list",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"number": "2520/06/2026/POL", "format": "PDF"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "filename": "zestawienie_2520_06_2026.pdf",
     *         "type": "application/pdf",
     *         "file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"
     *     }
     * }
     * ```
     *
     * Unlike getInvoicePdf(), the media type comes as `type` (`application/pdf` or `text/csv`) and
     * there is no invoice number in the response:
     * ```json
     * {"status": "success", "response": {"filename": "…", "type": "text/csv", "file": "…"}}
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nie znaleziono obiektu"}
     * ```
     * A missing `number` gives the same error.
     *
     * @param GetInvoiceSummaryListMessage $getInvoiceSummaryListMessage
     *
     * @return InvoiceSummaryListMessage payload:
     *         {@see \Polkurier\Webservice\Response\Invoice\InvoiceSummaryListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoiceSummaryList(
        GetInvoiceSummaryListMessage $getInvoiceSummaryListMessage
    ): InvoiceSummaryListMessage;
}
