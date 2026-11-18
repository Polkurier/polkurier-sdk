<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Webhook;

/**
 * What happened to the order a webhook is about.
 *
 * @api
 */
enum OrderEventTypeEnum: string
{
    /**
     * The order was created.
     */
    case Created = 'CREATED';

    /**
     * The order's status changed.
     */
    case Updated = 'UPDATED';

    /**
     * The order was cancelled.
     */
    case Cancelled = 'CANCELLED';
}
