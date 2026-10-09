<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Payment;

use Polkurier\Webservice\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of prepaid balance movements.
 *
 * Paged like the invoice list, and also without `totalpages`.
 */
readonly class PrepaidHistoryMessage extends PagedListMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_RESULT = 'result';
    /**-@#*/

    /**
     * @param int|null $totalRows movements across all pages
     * @param int|null $currentPage 1-based index of this page
     * @param int|null $pageSize movements per page as the API applied it
     * @param PrepaidEntryMessage[] $result the movements on this page
     */
    public function __construct(
        ?int $totalRows = 0,

        ?int $currentPage = 0,

        ?int $pageSize = 0,

        /**
         * @var PrepaidEntryMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
    ) {
        parent::__construct(totalRows: $totalRows, currentPage: $currentPage, pageSize: $pageSize);
    }

    /**
     * @return PrepaidEntryMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
