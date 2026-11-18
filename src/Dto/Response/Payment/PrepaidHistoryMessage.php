<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Payment;

use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of prepaid balance movements.
 *
 * @api
 */
final readonly class PrepaidHistoryMessage extends PagedListMessage
{
    /**
     * @param int $totalRows movements across all pages
     * @param int $currentPage 1-based index of this page
     * @param int $pageSize movements per page as the API applied it
     * @param PrepaidEntryMessage[] $result the movements on this page
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        int $totalRows = 0,

        int $currentPage = 0,

        int $pageSize = 0,

        /**
         * @var PrepaidEntryMessage[]
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
