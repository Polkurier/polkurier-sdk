<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Courier;

use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a check of whether a carrier can collect on one day. Nothing is booked.
 *
 * The date is optional, and today is checked without it.
 */
class PickupMessageBuilder extends PickupQueryMessageBuilder
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
