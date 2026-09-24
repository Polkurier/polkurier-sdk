<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the payout list.
 */
readonly class GetMoneyBoxPayoutsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAYOUT_NUMBER = 'payout_number';
    public const string FIELD_SEARCH_QUERY = 'search_query';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * @param ?string $payoutNumber narrow to one payout by its reference
     * @param ?string $searchQuery free-text search across the payouts
     * @param ?int $pageSize
     * @param ?int $page
     */
    public function __construct(
        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $payoutNumber = null,

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
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        #[OmitWhenEmpty]
        private ?int $pageSize = null,

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_PAGE)]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        #[OmitWhenEmpty]
        private ?int $page = null,
    ) {}

    /**
     * @return ?string
     */
    public function getPayoutNumber(): ?string
    {
        return $this->payoutNumber;
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
