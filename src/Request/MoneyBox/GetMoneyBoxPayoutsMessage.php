<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Request\Common\PagedMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the payout list.
 */
readonly class GetMoneyBoxPayoutsMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAYOUT_NUMBER = 'payout_number';
    public const string FIELD_SEARCH_QUERY = 'search_query';
    /**-@#*/

    /**
     * @param ?string $payoutNumber narrow to one payout by its reference
     * @param ?string $searchQuery matches the payout number, or the number, waybill number,
     *        recipient name or company of an order the payout covers
     * @param ?int $pageSize payouts per page, 1 when left out, capped at 100
     * @param ?int $page 1-based page index
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

        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }

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

}
