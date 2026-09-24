<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Message\ValidateCouponMessageValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Checks whether a discount coupon applies.
 *
 * Two levels of answer, and which you get depends on what you send. With only the code, the API
 * says whether the coupon is valid for the account at all. Supply the carrier, shipment type and
 * parcels as well and it additionally says whether it applies to that particular order — coupons
 * can be restricted by carrier, by value, or by sender region.
 *
 * The API requires all three order fields together or none: sending a carrier without parcels is
 * an error rather than a partial check.
 */
#[Validate\ValidatedBy(ValidateCouponMessageValidator::class)]
readonly class ValidateCouponMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COUPON_CODE = 'coupon_code';
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_PACKS = 'packs';
    public const string FIELD_SENDER = 'sender';
    /**-@#*/

    /**
     * @param string $couponCode
     * @param string|null $courier
     * @param ShipmentTypeEnum|null $shipmentType
     * @param PackMessage[] $packs
     * @param OrderAddressMessage|null $sender lets the API apply region-restricted coupons
     *
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUPON_CODE)]
        #[Validate\NotBlank]
        private string $couponCode,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[OmitWhenEmpty]
        private ?string $courier = null,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        #[OmitWhenEmpty]
        private ?ShipmentTypeEnum $shipmentType = null,

        /**
         * @var PackMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[Validate\Valid]
        #[OmitWhenEmpty]
        private array $packs = [],

        /**
         * @var OrderAddressMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_SENDER)]
        #[Validate\Valid]
        #[OmitWhenEmpty]
        private ?OrderAddressMessage $sender = null,
    ) {}

    /**
     * @return string
     */
    public function getCouponCode(): string
    {
        return $this->couponCode;
    }

    /**
     * @return string|null
     */
    public function getCourier(): ?string
    {
        return $this->courier;
    }

    /**
     * @return ShipmentTypeEnum|null
     */
    public function getShipmentType(): ?ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @return PackMessage[]
     */
    public function getPacks(): array
    {
        return $this->packs;
    }

    /**
     * @return OrderAddressMessage|null
     */
    public function getSender(): ?OrderAddressMessage
    {
        return $this->sender;
    }

}
