<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the invoice list.
 *
 * The type is mandatory and must be one of the two the API recognises; anything else is rejected
 * naming the acceptable set.
 */
readonly class GetInvoicesMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * @param InvoiceTypeEnum $type which document kind to list
     * @param ?int $pageSize invoices per page
     * @param ?int $page 1-based page index
     */
    public function __construct(
        /**
         * @var InvoiceTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private InvoiceTypeEnum $type,

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
     * @return InvoiceTypeEnum
     */
    public function getType(): InvoiceTypeEnum
    {
        return $this->type;
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
