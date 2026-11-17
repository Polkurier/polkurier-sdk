<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\MoneyBox;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the payout list.
 *
 * @api
 */
final readonly class GetMoneyBoxPayoutsMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAYOUT_NUMBER = 'payout_number';
    public const string FIELD_SEARCH_QUERY = 'search_query';
    /**-@#*/

    /**
     * @param string|null $payoutNumber narrow to one payout by its reference
     * @param string|null $searchQuery matches the payout number, or the number, waybill number,
     *        recipient name or company of an order the payout covers
     * @param int|null $pageSize payouts per page, 1 when left out, capped at 100
     * @param int|null $page 1-based page index
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_NUMBER)]
        #[OmitWhenEmpty]
        public ?string $payoutNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SEARCH_QUERY)]
        #[OmitWhenEmpty]
        public ?string $searchQuery = null,

        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
