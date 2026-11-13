<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Courier;

/**
 * Asks which days and time windows a carrier can collect on.
 *
 * Call this before booking so you only offer slots the carrier accepts.
 */
final readonly class GetPickupTimeMessage extends PickupQueryMessage
{
}
