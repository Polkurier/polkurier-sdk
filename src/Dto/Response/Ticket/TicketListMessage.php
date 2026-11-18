<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of support tickets.
 *
 * @api
 */
final readonly class TicketListMessage extends PagedListMessage
{
    /**
     * @param int $totalRows
     * @param int $currentPage
     * @param int $pageSize tickets per page, as applied to this response
     * @param TicketMessage[] $result the tickets on this page
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        int $totalRows = 0,

        int $currentPage = 0,

        int $pageSize = 0,

        /**
         * @var TicketMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        public array $result = [],
        ?string $rawResponse = null,
    ) {
        parent::__construct(
            totalRows: $totalRows,
            currentPage: $currentPage,
            pageSize: $pageSize,
            rawResponse: $rawResponse,
        );
    }
}
