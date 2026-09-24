<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of payout requests.
 *
 * Unlike the invoice and prepaid listings, this envelope does report `totalpages`.
 */
readonly class MoneyBoxPayoutListMessage implements MessageInterface
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
     * @param MoneyBoxPayoutMessage[] $result the payouts on this page
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
         * @var MoneyBoxPayoutMessage[]
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
    public function getTotalPages(): int
    {
        return $this->totalPages;
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
     * @return MoneyBoxPayoutMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
