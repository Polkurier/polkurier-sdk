<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the ticket list.
 *
 * Supplying an id narrows the list to that one ticket rather than returning it unwrapped — the
 * envelope is the same either way.
 */
readonly class GetTicketsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_SEARCH_QUERY = 'search_query';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * Largest page the API will serve. A bigger request is clamped rather than refused.
     */
    public const int MAX_PAGE_SIZE = 250;

    /**
     * @param ?string $id narrow to one ticket by UUID
     * @param ?string $searchQuery free-text search across the tickets
     * @param ?int $pageSize clamped to 1-250; defaults to 100
     * @param ?int $page 1-based page index
     */
    public function __construct(
        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_SEARCH_QUERY)]
        #[OmitWhenEmpty]
        private ?string $searchQuery = null,

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_PAGE_SIZE)]
        #[OmitWhenEmpty]
        #[Validate\IntegerRange(1, self::MAX_PAGE_SIZE)]
        private ?int $pageSize = null,

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_PAGE)]
        #[OmitWhenEmpty]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        private ?int $page = null,
    ) {}

    /**
     * @return ?string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return ?string
     */
    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    /**
     * @return ?int
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * @return ?int
     */
    public function getPage(): ?int
    {
        return $this->page;
    }
}
