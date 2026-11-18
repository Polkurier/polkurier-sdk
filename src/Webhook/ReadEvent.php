<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Webhook;

use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Serializer\ResponseDrift;

/**
 * An order event after it was read: what Polkurier posted, and how the order in it differed from
 * what the SDK expects.
 *
 * The drift belongs to this event alone.
 *
 * @api
 */
final readonly class ReadEvent
{
    /**
     * @param OrderEventMessage $event
     * @param list<ResponseDrift> $drift empty when the order was as expected
     */
    public function __construct(
        public OrderEventMessage $event,
        public array $drift = [],
    ) {}
}
