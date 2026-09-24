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
     * @param Cod $cod
     * @param Property $insurance
     * @param CourierService $courierService
     * @param ShipmentType $shipmentType
     * @param Pickup $pickup
     * @param AdditionalField[] $additionalFields
     */
    public function __construct(
        /**
         * @var Cod
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        private Cod $cod,

        /**
         * @var Property
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        private Property $insurance,

        /**
         * @var CourierService
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE)]
        private CourierService $courierService,

        /**
         * @var ShipmentType
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ShipmentType $shipmentType,

        /**
         * @var Pickup
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        private Pickup $pickup,

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
     * @return Cod
     */
    public function getCOD(): Cod
    {
        return $this->cod;
    }

    /**
     * @return Property
     */
    public function getInsurance(): Property
    {
        return $this->insurance;
    }

    /**
     * @return CourierService
     */
    public function getCourierService(): CourierService
    {
        return $this->courierService;
    }

    /**
     * @return ShipmentType
     */
    public function getShipmentType(): ShipmentType
    {
        return $this->shipmentType;
    }

    /**
     * @return Pickup
     */
    public function getPickup(): Pickup
    {
        return $this->pickup;
    }

    /**
     * @return PointsMap|null
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
