<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\MultiPickupRequestMessage;

/**
 * Assembles one collection covering several orders.
 */
class MultiPickupRequestMessageBuilder extends AbstractBuilder
{
    /**
     * @param \DateTimeInterface $pickupDate required before build()
     *
     * @return static
     */
    public function pickupDate(\DateTimeInterface $pickupDate): static
    {
        return $this->set('pickupDate', $pickupDate);
    }

    /**
     * @param \DateTimeInterface $pickupTimeFrom required before build()
     *
     * @return static
     */
    public function pickupTimeFrom(\DateTimeInterface $pickupTimeFrom): static
    {
        return $this->set('pickupTimeFrom', $pickupTimeFrom);
    }

    /**
     * @param \DateTimeInterface $pickupTimeTo required before build()
     *
     * @return static
     */
    public function pickupTimeTo(\DateTimeInterface $pickupTimeTo): static
    {
        return $this->set('pickupTimeTo', $pickupTimeTo);
    }

    /**
     * @param string[] $orders required before build()
     *
     * @return static
     */
    public function orders(array $orders): static
    {
        return $this->set('orders', $orders);
    }

    /**
     * @return MultiPickupRequestMessage
     * @throws InvalidArgumentException
     */
    public function build(): MultiPickupRequestMessage
    {
        return $this->make(MultiPickupRequestMessage::class);
    }
}
