<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Request\Order\PackMessage;

/**
 * Describes the shipment to ask about before offering a collective pickup.
 */
class MultiPickupAvailabilityRequestMessageBuilder extends AbstractBuilder
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
     * @param PackMessage[] $packs required before build()
     *
     * @return static
     */
    public function packs(array $packs): static
    {
        return $this->set('packs', $packs);
    }

    /**
     * @param string|null $senderPostCode
     *
     * @return static
     */
    public function senderPostCode(?string $senderPostCode): static
    {
        return $this->set('senderPostCode', $senderPostCode);
    }

    /**
     * @param ShipmentTypeEnum|null $shipmentType
     *
     * @return static
     */
    public function shipmentType(?ShipmentTypeEnum $shipmentType): static
    {
        return $this->set('shipmentType', $shipmentType);
    }

    /**
     * @return MultiPickupAvailabilityRequestMessage
     * @throws InvalidArgumentException
     */
    public function build(): MultiPickupAvailabilityRequestMessage
    {
        return $this->make(MultiPickupAvailabilityRequestMessage::class);
    }
}
