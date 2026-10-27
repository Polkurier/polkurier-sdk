<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A saved set of parcel details, as stored.
 *
 * To write one, use {@see \Polkurier\Webservice\Request\PackTemplate\SaveMessage}.
 *
 * An order cannot reference a template. To use one, read it and copy its values into the order
 * payload.
 */
readonly class PackTemplate extends BasePack
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_SHIPMENT_TYPE = 'shipmenttype';
    public const string FIELD_COD = 'COD';
    public const string FIELD_INSURANCE = 'insurance';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SERVICES = 'services';
    public const string FIELD_DEFAULT = 'default';
    /**-@#*/

    /**
     * @param int $length
     * @param int $width
     * @param int $height
     * @param float $weight
     * @param string|null $id
     * @param string $name
     * @param ShipmentTypeEnum|null $shipmentType
     * @param PackTypeEnum|null $type
     * @param float $cod
     * @param float $insurance
     * @param string $description
     * @param string|null $courier
     * @param array $services
     * @param bool $default not sent by the API, always false
     */
    public function __construct(
        int $length,

        int $width,

        int $height,

        float $weight,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ?ShipmentTypeEnum $shipmentType,

        ?PackTypeEnum $type = PackTypeEnum::Standard,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        private float $cod = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        private float $insurance = 0.0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        private ?string $courier = null,

        /**
         * @var AdditionalServiceEnum[]
         */
        #[Serializer\SerializedName(self::FIELD_SERVICES)]
        private array $services = [],

        /**
         * @var bool
         * @deprecated The API does not send this field; it is always false.
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,
    ) {
        parent::__construct($length, $width, $height, $weight, $type);
    }

    /**
     * @return string|null null if the response leaves it out
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ShipmentTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getShipmentType(): ?ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @return float
     */
    public function getCod(): float
    {
        return $this->cod;
    }

    /**
     * @return float
     */
    public function getInsurance(): float
    {
        return $this->insurance;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string|null null when the template isn't tied to a carrier
     */
    public function getCourier(): ?string
    {
        return $this->courier;
    }

    /**
     * @return AdditionalServiceEnum[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return bool
     * @deprecated The API does not send this field; it is always false.
     */
    public function isDefault(): bool
    {
        return $this->default;
    }
}
