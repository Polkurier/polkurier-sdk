<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Payment;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Paging for the prepaid balance history.
 */
final readonly class GetPrepaidHistoryMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    /**-@#*/

    /**
     * @param ?int $pageSize movements per page, defaults to 100, capped at 250
     * @param ?int $page 1-based page index
     */
    public function __construct(
        ?int $pageSize = null,

        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
