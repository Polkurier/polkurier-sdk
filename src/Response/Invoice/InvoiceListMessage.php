<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of the invoice list.
 *
 * Paged like the order list, but with no `totalpages` field. Work out the last page from the total
 * and the page size.
 */
readonly class InvoiceListMessage extends PagedListMessage
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
    ) {
        parent::__construct(totalRows: $totalRows, currentPage: $currentPage, pageSize: $pageSize);
    }

    /**
     * @return InvoiceMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
