<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Response\PagedListMessage;
use Polkurier\Webservice\Response\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of the order list.
 *
 * @see OrderListMessage
 */
readonly class OrderListMessage extends PagedListMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOTAL_PAGES = 'totalpages';
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int|null $totalRows
     * @param int|null $totalPages
     * @param int|null $currentPage
     * @param int|null $pageSize
     * @param Order[] $result
     */
    public function __construct(
        ?int $totalRows = 0,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_TOTAL_PAGES)]
        private ?int $totalPages = 0,

        ?int $currentPage = 0,

        ?int $pageSize = 0,

        /**
         * @var Order[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {
        parent::__construct(totalRows: $totalRows, currentPage: $currentPage, pageSize: $pageSize);
    }

    /**
     * @return int 0 when the API sends none
     */
    public function getTotalPages(): int
    {
        return $this->totalPages ?? 0;
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
