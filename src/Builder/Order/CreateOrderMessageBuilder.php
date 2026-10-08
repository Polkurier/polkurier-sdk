<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\CodMessage;
use Polkurier\Webservice\Request\Order\CoverAddressMessage;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\Order\PickupMessage;

/**
 * Assembles an order, for pricing or for placing.
 *
 * This is the largest payload in the SDK. The builder lets you set the parcels, addresses and
 * extras separately as you collect them.
 */
class CreateOrderMessageBuilder extends AbstractBuilder
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
     * @param ShipmentTypeEnum $shipmentType required before build()
     *
     * @return static
     */
    public function shipmentType(ShipmentTypeEnum $shipmentType): static
    {
        return $this->set('shipmentType', $shipmentType);
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
     * @param OrderAddressMessage $sender required before build()
     *
     * @return static
     */
    public function sender(OrderAddressMessage $sender): static
    {
        return $this->set('sender', $sender);
    }

    /**
     * @param OrderAddressMessage $recipient required before build()
     *
     * @return static
     */
    public function recipient(OrderAddressMessage $recipient): static
    {
        return $this->set('recipient', $recipient);
    }

    /**
     * @param CodMessage|null $cod
     *
     * @return static
     */
    public function cod(?CodMessage $cod): static
    {
        return $this->set('cod', $cod);
    }

    /**
     * @param float|null $insurance
     *
     * @return static
     */
    public function insurance(?float $insurance): static
    {
        return $this->set('insurance', $insurance);
    }

    /**
     * @param CoverAddressMessage|null $coverAddress
     *
     * @return static
     */
    public function coverAddress(?CoverAddressMessage $coverAddress): static
    {
        return $this->set('coverAddress', $coverAddress);
    }

    /**
     * @param PickupMessage|null $pickup
     *
     * @return static
     */
    public function pickup(?PickupMessage $pickup): static
    {
        return $this->set('pickup', $pickup);
    }

    /**
     * @param AdditionalServiceEnum[] $courierService
     *
     * @return static
     */
    public function courierService(array $courierService): static
    {
        return $this->set('courierService', $courierService);
    }

    /**
     * @param array<string, string> $additionalFields
     *
     * @return static
     */
    public function additionalFields(array $additionalFields): static
    {
        return $this->set('additionalFields', $additionalFields);
    }

    /**
     * @param string|null $description
     *
     * @return static
     */
    public function description(?string $description): static
    {
        return $this->set('description', $description);
    }

    /**
     * @param string|null $couponCode
     *
     * @return static
     */
    public function couponCode(?string $couponCode): static
    {
        return $this->set('couponCode', $couponCode);
    }

    /**
     * UUID of your application. Set it together with externalOrderId() to link the order to your
     * own record.
     *
     * @param string|null $externalApplicationId
     *
     * @return static
     */
    public function externalApplicationId(?string $externalApplicationId): static
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
    public function externalOrderId(?string $externalOrderId): static
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
    public function externalOrderNumber(?string $externalOrderNumber): static
    {
        return $this->set('externalOrderNumber', $externalOrderNumber);
    }

    /**
     * @return CreateOrderMessage
     * @throws InvalidArgumentException
     */
    public function build(): CreateOrderMessage
    {
        return $this->make(CreateOrderMessage::class);
    }
}
