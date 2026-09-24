<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * @see OrderListMessage
 */
readonly class OrderListMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOTAL_ROWS = 'totalrows';
    public const string FIELD_TOTAL_PAGES = 'totalpages';
    public const string FIELD_CURRENT_PAGE = 'currentpage';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int $totalRows
     * @param int $totalPages
     * @param int $currentPage
     * @param int $pageSize
     * @param Order[] $result
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
        #[Serializer\SerializedName(self::FIELD_TOTAL_PAGES)]
        private int $totalPages = 0,

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
         * @var Order[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {}

    /**
      * Total number of orders matching the filters, across all pages.
      *
     * @return int
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
      * 1-based index of the page in this response.
      *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
      * Orders per page as the API actually applied it, which is not necessarily the value requested:
      * it is clamped to 100, and 0 is treated as 100.
      *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
      * The orders on this page.
      *
     * @return Order[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
