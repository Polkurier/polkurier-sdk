<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Courier;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Courier\PickupMessage;

/**
 * Assembles a collection request that is not tied to an order.
 *
 * The date and the shipment type are optional. Which of them a carrier needs varies, and
 * available_carriers tells you.
 */
class PickupMessageBuilder extends GetPickupTimeMessageBuilder
{
    /**
     * @param \DateTimeInterface|null $pickupDate
     *
     * @return static
     */
    public function pickupDate(?\DateTimeInterface $pickupDate): static
    {
        return $this->set('pickupDate', $pickupDate);
    }

    /**
     * @return PickupMessage
     * @throws InvalidArgumentException
     */
    public function build(): PickupMessage
    {
        return $this->make(PickupMessage::class);
    }
}
