<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of support tickets.
 *
 * Paged like the invoice list, without a `totalpages` field. The page size must be 1–250 (the SDK
 * checks this before sending) and defaults to 100, a wider range than the order list allows.
 */
readonly class TicketListMessage implements MessageInterface
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
     * @param int $totalRows
     * @param int $currentPage
     * @param int $pageSize tickets per page, as applied to this response
     * @param TicketMessage[] $result the tickets on this page
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
         * @var TicketMessage[]
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
     * @return TicketMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
