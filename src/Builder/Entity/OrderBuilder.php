<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles an order, for pricing or for placing.
 *
 * This is the largest payload in the SDK. The builder lets you set the parcels, addresses and
 * extras separately as you collect them.
 *
 * @api
 */
class OrderBuilder extends AbstractBuilder
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
     * @param ShipmentTypeEnum $shipmentType required when sending
     *
     * @return static
     */
    public function setShipmentType(ShipmentTypeEnum $shipmentType): static
    {
        return $this->set('shipmentType', $shipmentType);
    }

    /**
     * @param PackItem[] $packs required when sending
     *
     * @return static
     */
    public function setPacks(array $packs): static
    {
        return $this->set('packs', $packs);
    }

    /**
     * @param Address $sender required when sending
     *
     * @return static
     */
    public function setSender(Address $sender): static
    {
        return $this->set('sender', $sender);
    }

    /**
     * @param Address $recipient required when sending
     *
     * @return static
     */
    public function setRecipient(Address $recipient): static
    {
        return $this->set('recipient', $recipient);
    }

    /**
     * @param Cod|null $cod
     *
     * @return static
     */
    public function setCod(?Cod $cod): static
    {
        return $this->set('cod', $cod);
    }

    /**
     * @param float|null $insurance
     *
     * @return static
     */
    public function setInsurance(?float $insurance): static
    {
        return $this->set('insurance', $insurance);
    }

    /**
     * @param Address|null $coverAddress
     *
     * @return static
     */
    public function setCoverAddress(?Address $coverAddress): static
    {
        return $this->set('coverAddress', $coverAddress);
    }

    /**
     * @param Pickup|null $pickup
     *
     * @return static
     */
    public function setPickup(?Pickup $pickup): static
    {
        return $this->set('pickup', $pickup);
    }

    /**
     * @param AdditionalServiceEnum[] $courierService
     *
     * @return static
     */
    public function setCourierService(array $courierService): static
    {
        return $this->set('courierService', $courierService);
    }

    /**
     * @param array<string, scalar> $additionalFields
     *
     * @return static
     */
    public function setAdditionalFields(array $additionalFields): static
    {
        return $this->set('additionalFields', $additionalFields);
    }

    /**
     * @param string|null $description
     *
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->set('description', $description);
    }

    /**
     * UUID of your application. Set it together with externalOrderId() to link the order to your
     * own record.
     *
     * @param string|null $externalApplicationId
     *
     * @return static
     */
    public function setExternalApplicationId(?string $externalApplicationId): static
    {
        return $this->set('externalApplicationId', $externalApplicationId);
    }

    /**
     * Your own id for the order. Needs externalApplicationId().
     *
     * @param string|null $externalOrderId
     *
     * @return static
     */
    public function setExternalOrderId(?string $externalOrderId): static
    {
        return $this->set('externalOrderId', $externalOrderId);
    }

    /**
     * Your own order number, e.g. the one the customer sees.
     *
     * @param string|null $externalOrderNumber
     *
     * @return static
     */
    public function setExternalOrderNumber(?string $externalOrderNumber): static
    {
        return $this->set('externalOrderNumber', $externalOrderNumber);
    }

    /**
     * @return Order
     * @throws InvalidArgumentException
     */
    public function build(): Order
    {
        return $this->make(Order::class);
    }
}
