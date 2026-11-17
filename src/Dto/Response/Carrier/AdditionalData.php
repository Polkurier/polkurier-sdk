<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\AdditionalField;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Cod;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\CourierService;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Pickup;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\PointsMap;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Property;
use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\ShipmentType;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a carrier accepts, from `available_carriers` with the additional data requested.
 *
 * Each part is null when the carrier's entry leaves it out. Treat it as a guide for building a
 * shipping form: whether an order is accepted is decided when it's placed. The SDK doesn't check
 * an order against it.
 *
 * @api
 */
final readonly class AdditionalData implements MessageInterface
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
     * @param PointsMap|null $pointsMap
     * @param AdditionalField[] $additionalFields
     */
    public function __construct(
        /**
         * @var Cod|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        public ?Cod $cod = null,

        /**
         * @var Property|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        public ?Property $insurance = null,

        /**
         * @var CourierService|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_SERVICE)]
        public ?CourierService $courierService = null,

        /**
         * @var ShipmentType|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        public ?ShipmentType $shipmentType = null,

        /**
         * @var Pickup|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP)]
        public ?Pickup $pickup = null,

        /**
         * @var PointsMap|null null if the carrier's entry leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_POINTS_MAP)]
        #[OmitWhenEmpty]
        public ?PointsMap $pointsMap = null,

        /**
         * @var AdditionalField[]
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_FIELDS)]
        public array $additionalFields = [],
    ) {}
}
