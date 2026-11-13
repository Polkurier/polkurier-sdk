<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Invoice;

use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of the invoice list.
 *
 * Paged like the order list, but with no `totalpages` field. Work out the last page from the total
 * and the page size.
 */
final readonly class InvoiceListMessage extends PagedListMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int|null $totalRows invoices matching the filter, across all pages
     * @param int|null $currentPage 1-based index of this page
     * @param int|null $pageSize invoices per page as the API applied it
     * @param InvoiceMessage[] $result the invoices on this page
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        ?int $totalRows = 0,

        ?int $currentPage = 0,

        ?int $pageSize = 0,

        /**
         * @var InvoiceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
        ?string $rawResponse = null,
    ) {
        parent::__construct(
            totalRows: $totalRows,
            currentPage: $currentPage,
            pageSize: $pageSize,
            rawResponse: $rawResponse,
        );
    }

    /**
     * @return InvoiceMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
