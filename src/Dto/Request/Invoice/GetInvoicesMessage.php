<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Invoice;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;
use Polkurier\Webservice\Enum\Invoice\InvoiceTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters and paging for the invoice list.
 */
final readonly class GetInvoicesMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param InvoiceTypeEnum $type which kind of document to list
     * @param ?int $pageSize invoices per page, defaults to 100, capped at 250
     * @param ?int $page 1-based page index
     */
    public function __construct(
        /**
         * @var InvoiceTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public InvoiceTypeEnum $type,

        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
