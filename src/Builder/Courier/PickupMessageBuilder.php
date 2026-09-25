<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Courier;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Courier\PickupMessage;

/**
 * Assembles a collection request that is not tied to an order.
 *
 * The date and the shipment type are optional. Which of them a carrier needs varies, and
 * available_carriers tells you.
 */
class PickupMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $courier required before build()
     *
     * @return static
     */
    public function courier(string $courier): static
    {
        return $this->set('courier', $courier);
    }

    /**
     * @param string $shipFrom required before build()
     *
     * @return static
     */
    public function shipFrom(string $shipFrom): static
    {
        return $this->set('shipFrom', $shipFrom);
    }

    /**
     * @param string|null $shipTo
     *
     * @return static
     */
    public function shipTo(?string $shipTo): static
    {
        return $this->set('shipTo', $shipTo);
    }

    /**
     * @param ShipmentTypeEnum|null $parcel
     *
     * @return static
     */
    public function parcel(?ShipmentTypeEnum $parcel): static
    {
        return $this->set('parcel', $parcel);
    }

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
