<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\PackTemplate;

use Polkurier\Webservice\Entity\Pack\BasePackMessage;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A parcel template to save on the account.
 *
 * Pass an id to update that template, or leave it out to create a new one. An id that names none of
 * the account's templates creates a new one.
 *
 * The services `ADR`, `DO_NOT_STACK`, `REQUIRE_LIFT_FOR_LOAD`, `REQUIRE_LIFT_FOR_UNLOAD`,
 * `SIDE_LOAD` and `SIDE_UNLOAD` aren't stored on a template, and a carrier code the API doesn't know
 * is stored as no carrier.
 */
readonly class SavePackTemplateMessage extends BasePackMessage
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
    /**-@#*/

    /**
     * @param string $name the label the template is listed under
     * @param int $length in whole centimetres
     * @param int $width in whole centimetres
     * @param int $height in whole centimetres
     * @param float $weight in kilograms
     * @param ShipmentTypeEnum $shipmentType
     * @param PackTypeEnum $type the shape the carrier prices it as
     * @param string|null $id the template to update, or null to create a new one
     * @param float $cod amount to collect on delivery, in PLN
     * @param float $insurance declared value, in PLN
     * @param string $description
     * @param string|null $courier the template's carrier code, if any
     * @param AdditionalServiceEnum[] $services extras to preselect
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        #[Validate\NotBlank]
        private string $name,

        int $length,
        int $width,
        int $height,
        float $weight,

        /**
         * @var ShipmentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        private ShipmentTypeEnum $shipmentType = ShipmentTypeEnum::Box,

        PackTypeEnum $type = PackTypeEnum::Standard,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_COD)]
        #[Validate\NonNegative]
        private float $cod = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INSURANCE)]
        #[Validate\NonNegative]
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
        #[OmitWhenEmpty]
        private ?string $courier = null,

        /**
         * @var AdditionalServiceEnum[]
         */
        #[Serializer\SerializedName(self::FIELD_SERVICES)]
        #[Validate\Each(Validate\OfType::class, AdditionalServiceEnum::class)]
        private array $services = [],
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ShipmentTypeEnum
     */
    public function getShipmentType(): ShipmentTypeEnum
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
     * @return string|null
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
}
