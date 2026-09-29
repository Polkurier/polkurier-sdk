<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of support tickets.
 *
 * Paged like the invoice list, without a `totalpages` field. The page size must be 1–250 (the SDK
 * checks this before sending) and defaults to 100, a wider range than the order list allows.
 */
readonly class TicketListMessage extends PagedListMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int $totalRows
     * @param int $currentPage
     * @param int $pageSize tickets per page, as applied to this response
     * @param TicketMessage[] $result the tickets on this page
     */
    public function __construct(
        int $totalRows = 0,

        int $currentPage = 0,

        int $pageSize = 0,

        /**
         * @var TicketMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {
        parent::__construct(totalRows: $totalRows, currentPage: $currentPage, pageSize: $pageSize);
    }

    /**
     * @return TicketMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
