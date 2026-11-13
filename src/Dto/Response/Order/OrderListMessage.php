<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Entity\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of the order list.
 *
 * @api
 */
final readonly class OrderListMessage extends PagedListMessage
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
     * @param Order[] $result
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        int $totalRows = 0,

        /**
         * @var int 0 when the API sends none
         */
        #[Serializer\SerializedName(self::FIELD_TOTAL_PAGES)]
        public int $totalPages = 0,

        int $currentPage = 0,

        int $pageSize = 0,

        /**
         * The orders on this page.
         *
         * @var Order[]
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
