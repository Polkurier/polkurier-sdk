<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Paging for the prepaid balance history.
 */
readonly class GetPrepaidHistoryMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * @param ?int $pageSize movements per page
     * @param ?int $page 1-based page index
     */
    public function __construct(
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
