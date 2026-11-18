<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\Courier;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;

/**
 * The fields shared by the builders of the collection queries: the carrier and the route.
 *
 * The destination and the shipment type are optional.
 *
 * @api
 */
abstract class PickupQueryMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $courier required before build()
     *
     * @return static
     */
    public function setCourier(string $courier): static
    {
        return $this->set('courier', $courier);
    }

    /**
     * @param string $shipFrom required before build()
     *
     * @return static
     */
    public function setShipFrom(string $shipFrom): static
    {
        return $this->set('shipFrom', $shipFrom);
    }

    /**
     * @param string|null $shipTo
     *
     * @return static
     */
    public function setShipTo(?string $shipTo): static
    {
        return $this->set('shipTo', $shipTo);
    }

    /**
     * @param ShipmentTypeEnum|null $parcel
     *
     * @return static
     */
    public function setParcel(?ShipmentTypeEnum $parcel): static
    {
        return $this->set('parcel', $parcel);
    }
}
