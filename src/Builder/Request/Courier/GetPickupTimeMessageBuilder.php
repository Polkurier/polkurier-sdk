<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\Courier;

use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a query for the times a carrier can collect.
 *
 * The destination and the shipment type are optional.
 *
 * @api
 */
class GetPickupTimeMessageBuilder extends PickupQueryMessageBuilder
{
    /**
     * @return GetPickupTimeMessage
     * @throws InvalidArgumentException
     */
    public function build(): GetPickupTimeMessage
    {
        return $this->make(GetPickupTimeMessage::class);
    }
}
