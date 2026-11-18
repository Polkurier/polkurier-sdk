<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Invoice\GetInvoiceDocumentMessage;
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoicesMessage;
use Polkurier\Webservice\Dto\Request\Invoice\GetInvoiceSummaryListMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceDocumentMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceListMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceSummaryListMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Invoices issued to the account, and the documents behind them.
 *
 * Invoices and corrections are listed separately.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 *
 * @api
 */
interface InvoicesServiceInterface
{
    /**
     * Lists invoices, one page at a time.
     *
     * The document type is required. Invoices and corrections are separate lists, newest first.
     *
     * Page size defaults to 100 and is capped at 250. Unlike the order list, the paging envelope has
     * no `totalpages`. Work out the last page from the total and the page size.
     *
     * For reconciliation, use `unpaid_value`: what is left after part payments and compensations,
     * zero once settled. Corrections carry only `number`, `value` and `date`; their unpaid value
     * reads as zero either way.
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
     * An account with no invoices of that kind gets `"totalrows": 0` and `"result": []`.
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
     *         {@see \Polkurier\Webservice\Dto\Response\Invoice\InvoiceListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInvoices(GetInvoicesMessage $getInvoicesMessage): InvoiceListMessage;

    /**
     * Downloads one invoice or correction as a PDF.
     *
     * Pass the number from getInvoices() as returned, slashes included.
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
     *         "filename": "2520-06-2026-POL.pdf",
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
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Dokument został przekazany do KSeF i obecnie oczekuje na nadanie numeru identyfikacyjnego przez system. Po zakończeniu procesu dokument będzie dostępny"}
     * ```
     * The document is waiting for its KSeF number. Try again later.
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [number]"}
     * ```
     * Missing or blank number, when payload validation is off.
     *
     * @param GetInvoiceDocumentMessage $getInvoiceDocumentMessage
     *
     * @return InvoiceDocumentMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Invoice\InvoiceDocumentMessage}
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
     * This is the breakdown of the shipments an invoice covers, for reconciling a monthly invoice
     * against individual orders.
     *
     * Pass the invoice number, as for getInvoicePdf(), and the format: PDF to read or print, CSV to
     * import. The SDK sends PDF unless you choose otherwise.
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
     *         "filename": "lista_2520-06-2026-POL.pdf",
     *         "type": "application/pdf",
     *         "file": "JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBl…"
     *     }
     * }
     * ```
     *
     * Unlike getInvoicePdf(), the media type comes as `type`: `application/pdf` or `text/csv`.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nie znaleziono obiektu"}
     * ```
     * Thrown for an unknown number and for a correction's, and for a missing or blank one when
     * payload validation is off.
     *
     * @param GetInvoiceSummaryListMessage $getInvoiceSummaryListMessage
     *
     * @return InvoiceSummaryListMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Invoice\InvoiceSummaryListMessage}
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
