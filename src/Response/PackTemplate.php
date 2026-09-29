<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
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
 * To write one, use {@see \Polkurier\Webservice\Request\PackTemplate\SaveMessage}. Whether a
 * template is the account's default is read-only.
 *
 * An order cannot reference a template. To use one, read it and copy its values into the order
 * payload.
 */
class PackTemplate extends BasePack
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
     * @param ShipmentTypeEnum $shipmentType
     * @param PackTypeEnum $type
     * @param float $cod
     * @param float $insurance
     * @param string $description
     * @param string|null $courier
     * @param array $services
     * @param bool $default
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
         * @var ShipmentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ShipmentTypeEnum $shipmentType,

        PackTypeEnum $type = PackTypeEnum::Standard,

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
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,
    ) {
        parent::__construct($length, $width, $height, $weight, $type);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ShipmentTypeEnum
     */
    public function getShipmentType(): ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @param ShipmentTypeEnum $shipmentType
     *
     * @return void
     */
    public function setShipmentType(ShipmentTypeEnum $shipmentType): void
    {
        $this->shipmentType = $shipmentType;
    }

    /**
     * @return float
     */
    public function getCod(): float
    {
        return $this->cod;
    }

    /**
     * @param float $cod
     *
     * @return void
     */
    public function setCod(float $cod): void
    {
        $this->cod = $cod;
    }

    /**
     * @return float
     */
    public function getInsurance(): float
    {
        return $this->insurance;
    }

    /**
     * @param float $insurance
     *
     * @return void
     */
    public function setInsurance(float $insurance): void
    {
        $this->insurance = $insurance;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getCourier(): ?string
    {
        return $this->courier;
    }

    /**
     * @param string|null $courier
     *
     * @return void
     */
    public function setCourier(?string $courier): void
    {
        $this->courier = $courier;
    }

    /**
     * @return AdditionalServiceEnum[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param AdditionalServiceEnum[] $services
     *
     * @return void
     */
    public function setServices(array $services): void
    {
        $this->services = $services;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     *
     * @return void
     */
    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
