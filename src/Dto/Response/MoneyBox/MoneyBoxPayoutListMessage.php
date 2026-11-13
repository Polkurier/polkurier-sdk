<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One page of payout requests.
 *
 * This envelope includes `totalpages`, unlike the invoice and prepaid listings.
 */
final readonly class MoneyBoxPayoutListMessage extends PagedListMessage
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
     * @param MoneyBoxPayoutMessage[] $result the payouts on this page
     * @param string|null $rawResponse the body of the response, as the API sent it
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
         * @var MoneyBoxPayoutMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_RESULT)]
        private array $result = [],
        ?string $rawResponse = null,
    ) {
        parent::__construct(
            totalRows: $totalRows,
            currentPage: $currentPage,
            pageSize: $pageSize,
            rawResponse: $rawResponse,
        );
    }

    /**
     * @return int 0 when the API sends none
     */
    public function getTotalPages(): int
    {
        return $this->totalPages ?? 0;
    }

    /**
     * @return MoneyBoxPayoutMessage[]
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
