<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The paging fields every page of a list carries.
 *
 * The rows themselves, and `totalpages` where a list has it, are on the subclass.
 */
abstract readonly class PagedListMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOTAL_ROWS = 'totalrows';
    public const string FIELD_CURRENT_PAGE = 'currentpage';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    /**-@#*/

    /**
     * @param int|null $totalRows rows across all pages
     * @param int|null $currentPage 1-based index of this page
     * @param int|null $pageSize rows per page, as applied to this response
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_TOTAL_ROWS)]
        private ?int $totalRows = 0,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_CURRENT_PAGE)]
        private ?int $currentPage = 0,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_PAGE_SIZE)]
        private ?int $pageSize = 0,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return int 0 when the API sends none
     */
    public function getTotalRows(): int
    {
        return $this->totalRows ?? 0;
    }

    /**
     * @return int 0 when the API sends none
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage ?? 0;
    }

    /**
     * @return int 0 when the API sends none
     */
    public function getPageSize(): int
    {
        return $this->pageSize ?? 0;
    }
}
