<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Webhook;

/**
 * What happened to the order a webhook is about.
 */
enum OrderWebhookTypeEnum: string
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
