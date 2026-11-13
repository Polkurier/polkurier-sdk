<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity;

use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
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
 *
 * @api
 */
class PackTemplate extends Pack implements ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param ShipmentTypeEnum|null $shipmentType
     * @param PackTypeEnum|null $type the shape the carrier prices it as
     * @param string|null $id the template to update, or null to create a new one
     * @param float $cod amount to collect on delivery, in PLN
     * @param float $insurance declared value, in PLN
     * @param string $description
     * @param string|null $courier the template's carrier code, if any
     * @param AdditionalServiceEnum[] $services extras to preselect
     * @param string|null $rawResponse the body of the response, as the API sent it
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
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        #[Validate\NotBlank]
        private ?ShipmentTypeEnum $shipmentType = ShipmentTypeEnum::Box,

        ?PackTypeEnum $type = PackTypeEnum::Standard,

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
        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
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
     * @return static
     */
    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
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
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return ShipmentTypeEnum|null
     */
    public function getShipmentType(): ?ShipmentTypeEnum
    {
        return $this->shipmentType;
    }

    /**
     * @param ShipmentTypeEnum|null $shipmentType
     *
     * @return static
     */
    public function setShipmentType(?ShipmentTypeEnum $shipmentType): static
    {
        $this->shipmentType = $shipmentType;

        return $this;
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
     * @return static
     */
    public function setCod(float $cod): static
    {
        $this->cod = $cod;

        return $this;
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
     * @return static
     */
    public function setInsurance(float $insurance): static
    {
        $this->insurance = $insurance;

        return $this;
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
     * @return static
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
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
     * @return static
     */
    public function setCourier(?string $courier): static
    {
        $this->courier = $courier;

        return $this;
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
     * @return static
     */
    public function setServices(array $services): static
    {
        $this->services = $services;

        return $this;
    }

}
