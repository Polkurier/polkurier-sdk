<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\Carrier\AdditionalData\AdditionalField;
use Polkurier\Webservice\Response\Carrier\AdditionalData\Cod;
use Polkurier\Webservice\Response\Carrier\AdditionalData\CourierService;
use Polkurier\Webservice\Response\Carrier\AdditionalData\Pickup;
use Polkurier\Webservice\Response\Carrier\AdditionalData\PointsMap;
use Polkurier\Webservice\Response\Carrier\AdditionalData\Property;
use Polkurier\Webservice\Response\Carrier\AdditionalData\ShipmentType;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a carrier accepts, from `available_carriers` with the additional data requested.
 *
 * Each part is null when the carrier's entry leaves it out. Treat it as a guide for building a
 * shipping form: whether an order is accepted is decided when it's placed. The SDK doesn't check
 * an order against it.
 */
readonly class AdditionalData implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COD = 'COD';
    public const string FIELD_INSURANCE = 'insurance';
    public const string FIELD_COURIER_SERVICE = 'courierservice';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_PICKUP = 'pickup';
    public const string FIELD_POINTS_MAP = 'points_map';
    public const string FIELD_ADDITIONAL_FIELDS = 'additional_fields';
    /**-@#*/

    /**
     * @param Cod|null $cod
     * @param Property|null $insurance
     * @param CourierService|null $courierService
     * @param ShipmentType|null $shipmentType
     * @param Pickup|null $pickup
     * @param AdditionalField[] $additionalFields
     */
    public function __construct(
        /**
         * @var Cod|null
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        private ?Cod $cod = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        private ?Property $insurance = null,

        /**
         * @var CourierService|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE)]
        private ?CourierService $courierService = null,

        /**
         * @var ShipmentType|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ?ShipmentType $shipmentType = null,

        /**
         * @var Pickup|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        private ?Pickup $pickup = null,

        /**
         * @var PointsMap|null
         */
        #[Serializer\SerializedName(self::FIELD_POINTS_MAP)]
        #[OmitWhenEmpty]
        private ?PointsMap $pointsMap = null,

        /**
         * @var AdditionalField[] $additionalFields
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_FIELDS)]
        private array $additionalFields = [],
    ) {}

    /**
     * @return Cod|null null if the carrier's entry leaves it out
     */
    public function getCod(): ?Cod
    {
        return $this->cod;
    }

    /**
     * @return Property|null null if the carrier's entry leaves it out
     */
    public function getInsurance(): ?Property
    {
        return $this->insurance;
    }

    /**
     * @return CourierService|null null if the carrier's entry leaves it out
     */
    public function getCourierService(): ?CourierService
    {
        return $this->courierService;
    }

    /**
     * @return ShipmentType|null null if the carrier's entry leaves it out
     */
    public function getShipmentType(): ?ShipmentType
    {
        return $this->shipmentType;
    }

    /**
     * @return Pickup|null null if the carrier's entry leaves it out
     */
    public function getPickup(): ?Pickup
    {
        return $this->pickup;
    }

    /**
     * @return PointsMap|null null if the carrier's entry leaves it out
     */
    public function getPointsMap(): ?PointsMap
    {
        return $this->pointsMap;
    }

    /**
     * @return AdditionalField[]
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }
}
