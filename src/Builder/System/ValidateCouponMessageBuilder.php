<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\System;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Entity\Order\OrderAddressMessage;
use Polkurier\Webservice\Entity\Order\PackMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a coupon check.
 *
 * With only the code, the check tells you whether the coupon is valid. If you also set the carrier,
 * the shipment type and the parcels, it also checks whether the coupon applies to that order. Set
 * all 3 of them or none.
 */
class ValidateCouponMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $couponCode required before build()
     *
     * @return static
     */
    public function couponCode(string $couponCode): static
    {
        return $this->set('couponCode', $couponCode);
    }

    /**
     * @param string|null $courier
     *
     * @return static
     */
    public function courier(?string $courier): static
    {
        return $this->set('courier', $courier);
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
     * @param PackMessage[] $packs
     *
     * @return static
     */
    public function packs(array $packs): static
    {
        return $this->set('packs', $packs);
    }

    /**
     * @param OrderAddressMessage|null $sender
     *
     * @return static
     * @deprecated The API does not read this field.
     */
    public function sender(?OrderAddressMessage $sender): static
    {
        return $this->set('sender', $sender);
    }

    /**
     * @return ValidateCouponMessage
     * @throws InvalidArgumentException
     */
    public function build(): ValidateCouponMessage
    {
        return $this->make(ValidateCouponMessage::class);
    }
}
