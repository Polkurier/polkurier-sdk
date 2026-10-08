<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\PickupMessage;

/**
 * Assembles the collection details of an order.
 *
 * Every field is optional. Carriers differ in what they need, and not every carrier uses these
 * fields.
 */
class PickupMessageBuilder extends AbstractBuilder
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
     * @param \DateTimeInterface|null $pickupTimeFrom
     *
     * @return static
     */
    public function pickupTimeFrom(?\DateTimeInterface $pickupTimeFrom): static
    {
        return $this->set('pickupTimeFrom', $pickupTimeFrom);
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeTo
     *
     * @return static
     */
    public function pickupTimeTo(?\DateTimeInterface $pickupTimeTo): static
    {
        return $this->set('pickupTimeTo', $pickupTimeTo);
    }

    /**
     * @param bool $noCourierOrder
     *
     * @return static
     */
    public function noCourierOrder(bool $noCourierOrder): static
    {
        return $this->set('noCourierOrder', $noCourierOrder);
    }

    /**
     * @param bool $multiPickup
     *
     * @return static
     */
    public function multiPickup(bool $multiPickup): static
    {
        return $this->set('multiPickup', $multiPickup);
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
