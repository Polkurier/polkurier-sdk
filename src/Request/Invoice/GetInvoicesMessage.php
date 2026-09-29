<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Invoice;

use Polkurier\Webservice\Request\Common\PagedMessage;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the invoice list.
 *
 * The type is required and must be one of the 2 the API accepts. Any other value is rejected with
 * an error listing the accepted ones.
 */
readonly class GetInvoicesMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param InvoiceTypeEnum $type which kind of document to list
     * @param ?int $pageSize invoices per page
     * @param ?int $page 1-based page index
     */
    public function __construct(
        /**
         * @var InvoiceTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private InvoiceTypeEnum $type,

        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }

    /**
     * @return InvoiceTypeEnum
     */
    public function getType(): InvoiceTypeEnum
    {
        return $this->type;
    }

}
