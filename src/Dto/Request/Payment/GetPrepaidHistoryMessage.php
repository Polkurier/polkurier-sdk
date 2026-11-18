<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Payment;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;

/**
 * Paging for the prepaid balance history.
 *
 * @api
 */
final readonly class GetPrepaidHistoryMessage extends PagedMessage
{
    /**
     * @param int|null $pageSize movements per page, defaults to 100, capped at 250
     * @param int|null $page 1-based page index
     */
    public function __construct(
        ?int $pageSize = null,
        ?int $page = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
