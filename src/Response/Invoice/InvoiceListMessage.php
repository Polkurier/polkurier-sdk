<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of the invoice list.
 *
 * Paged like the order list, but note the envelope is one field shorter: there is no `totalpages`
 * here, so the last page has to be inferred from the total and the page size.
 */
readonly class InvoiceListMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOTAL_ROWS = 'totalrows';
    public const string FIELD_CURRENT_PAGE = 'currentpage';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int $totalRows invoices matching the filter, across all pages
     * @param int $currentPage 1-based index of this page
     * @param int $pageSize invoices per page as the API applied it
     * @param InvoiceMessage[] $result the invoices on this page
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_TOTAL_ROWS)]
        private int $totalRows = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_CURRENT_PAGE)]
        private int $currentPage = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_PAGE_SIZE)]
        private int $pageSize = 0,

        /**
         * @var InvoiceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {}

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return InvoiceMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
