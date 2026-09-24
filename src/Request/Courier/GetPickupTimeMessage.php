<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Asks which days and time windows a carrier can collect on.
 *
 * Worth calling before booking: it avoids offering the user a slot the carrier will refuse.
 */
readonly class GetPickupTimeMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SHIP_FROM = 'shipfrom';
    public const string FIELD_SHIP_TO = 'shipto';
    public const string FIELD_PARCEL = 'parcel';
    /**-@#*/

    /**
     * @param string $courier
     * @param string $shipFrom
     * @param string|null $shipTo
     * @param ShipmentTypeEnum|null $parcel
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        private string $courier,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SHIP_FROM)]
        #[Validate\NotBlank]
        #[Validate\Postcode]
        private string $shipFrom,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIP_TO)]
        #[Validate\Postcode]
        #[OmitWhenEmpty]
        private ?string $shipTo = null,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_PARCEL)]
        #[OmitWhenEmpty]
        private ?ShipmentTypeEnum $parcel = null,
    ) {}

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * @return string
     */
    public function getShipFrom(): string
    {
        return $this->shipFrom;
    }

    /**
     * @return string|null
     */
    public function getShipTo(): ?string
    {
        return $this->shipTo;
    }

    /**
     * @return ShipmentTypeEnum|null
     */
    public function getParcel(): ?ShipmentTypeEnum
    {
        return $this->parcel;
    }
}
