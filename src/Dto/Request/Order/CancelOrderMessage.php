<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

/**
 * Asks for an order to be cancelled.
 *
 * @api
 */
final readonly class CancelOrderMessage extends OrderIdMessage
{
}
