<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Ticket;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the ticket list.
 *
 * Setting an id narrows the list to that ticket, with its timeline, and ignores the other filters.
 * The response is still a list.
 *
 * @api
 */
#[Validate\PageSizeAtMost(self::MAX_PAGE_SIZE)]
final readonly class GetTicketsMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_SEARCH_QUERY = 'search_query';
    /**-@#*/

    /**
     * Largest page size. Larger values fail validation.
     */
    public const int MAX_PAGE_SIZE = 250;

    /**
     * @param string|null $id narrow to one ticket by UUID
     * @param string|null $searchQuery words to find in the subject, the ticket number or the numbers of
     *        the orders, waybills, complaints and invoices it refers to; every word must match
     * @param int|null $pageSize 1-250, defaults to 100
     * @param int|null $page 1-based page index
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        public ?string $id = null,

        #[Serializer\SerializedName(self::FIELD_SEARCH_QUERY)]
        #[OmitWhenEmpty]
        public ?string $searchQuery = null,

        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
