<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\MoneyBox;

use Polkurier\Webservice\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of payout requests.
 *
 * This envelope includes `totalpages`, unlike the invoice and prepaid listings.
 */
readonly class MoneyBoxPayoutListMessage extends PagedListMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOTAL_PAGES = 'totalpages';
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
        int $totalRows = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_TOTAL_PAGES)]
        private int $totalPages = 0,

        int $currentPage = 0,

        int $pageSize = 0,

        /**
         * @var MoneyBoxPayoutMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {
        parent::__construct(totalRows: $totalRows, currentPage: $currentPage, pageSize: $pageSize);
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * @return MoneyBoxPayoutMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
