<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles the collection details of an order.
 *
 * Every field is optional. Carriers differ in what they need, and not every carrier uses these
 * fields.
 *
 * @api
 */
class PickupBuilder extends AbstractBuilder
{
    /**
     * @param \DateTimeInterface|null $pickupDate
     *
     * @return static
     */
    public function setPickupDate(?\DateTimeInterface $pickupDate): static
    {
        return $this->set('pickupDate', $pickupDate);
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeFrom
     *
     * @return static
     */
    public function setPickupTimeFrom(?\DateTimeInterface $pickupTimeFrom): static
    {
        return $this->set('pickupTimeFrom', $pickupTimeFrom);
    }

    /**
     * @param \DateTimeInterface|null $pickupTimeTo
     *
     * @return static
     */
    public function setPickupTimeTo(?\DateTimeInterface $pickupTimeTo): static
    {
        return $this->set('pickupTimeTo', $pickupTimeTo);
    }

    /**
     * @param bool $noCourierOrder
     *
     * @return static
     */
    public function setNoCourierOrder(bool $noCourierOrder): static
    {
        return $this->set('noCourierOrder', $noCourierOrder);
    }

    /**
     * @param bool $multiPickup
     *
     * @return static
     */
    public function setMultiPickup(bool $multiPickup): static
    {
        return $this->set('multiPickup', $multiPickup);
    }

    /**
     * @return Pickup
     * @throws InvalidArgumentException
     */
    public function build(): Pickup
    {
        return $this->make(Pickup::class);
    }
}
