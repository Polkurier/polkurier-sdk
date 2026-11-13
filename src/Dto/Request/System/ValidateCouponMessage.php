<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Message\ValidateCouponMessageValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Checks whether a discount coupon applies.
 *
 * With only the code, the API says whether the coupon is valid for the account. If you also send
 * the carrier, shipment type and parcels, it also says whether the coupon applies to that order.
 * Coupons can be restricted by carrier, shipment type and parcel size or weight.
 *
 * Send all 3 order fields or none. A carrier without parcels, for example, is an error.
 *
 * @api
 */
#[Validate\ValidatedBy(ValidateCouponMessageValidator::class)]
final readonly class ValidateCouponMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COUPON_CODE = 'coupon_code';
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_PACKS = 'packs';
    /**-@#*/

    /**
     * @param string $couponCode
     * @param string|null $courier
     * @param ShipmentTypeEnum|null $shipmentType
     * @param PackItem[] $packs
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUPON_CODE)]
        #[Validate\NotBlank]
        public string $couponCode,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[OmitWhenEmpty]
        public ?string $courier = null,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        #[OmitWhenEmpty]
        public ?ShipmentTypeEnum $shipmentType = null,

        /**
         * @var PackItem[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[Validate\Valid]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\OfType::class, PackItem::class)]
        public array $packs = [],
    ) {}
}
