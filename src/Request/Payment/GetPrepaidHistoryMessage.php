<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Payment;

use Polkurier\Webservice\Request\Common\PagedMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Paging for the prepaid balance history.
 */
readonly class GetPrepaidHistoryMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    /**-@#*/

    /**
     * @param ?int $pageSize movements per page
     * @param ?int $page 1-based page index
     */
    public function __construct(
        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }

}
