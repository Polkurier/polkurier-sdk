<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Entity\Pack;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One parcel on an order being priced or placed.
 *
 * The amount is a multiplier for identical parcels. Send 3 boxes of the same size as one entry with
 * an amount of 3. Carriers that price per consignment still charge once for it, so this is not the
 * same as sending 3 separate entries.
 *
 * @api
 */
class PackItem extends Pack
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_PRICE_NET = 'price_net';
    public const string FIELD_PRICE_GROSS = 'price_gross';
    /**-@#*/

    /**
     * @param int $length in whole centimetres
     * @param int $width in whole centimetres
     * @param int $height in whole centimetres
     * @param float $weight in kilograms
     * @param PackTypeEnum|null $type the shape the carrier prices it as
     * @param int $amount how many identical parcels of these dimensions
     * @param float|null $priceNet
     * @param float|null $priceGross
     */
    public function __construct(
        int $length,
        int $width,
        int $height,
        float $weight,
        ?PackTypeEnum $type = PackTypeEnum::Standard,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        #[Validate\Positive]
        private int $amount = 1,
        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        #[ResponseOnly]
        private ?float $priceNet = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
        #[ResponseOnly]
        private ?float $priceGross = null,
    ) {
        parent::__construct($length, $width, $height, $weight, $type);
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @return static
     */
    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float|null null if the response leaves it out
     */
    public function getPriceNet(): ?float
    {
        return $this->priceNet;
    }

    /**
     * @param float|null $priceNet
     *
     * @return static
     */
    public function setPriceNet(?float $priceNet): static
    {
        $this->priceNet = $priceNet;

        return $this;
    }

    /**
     * @return float|null null if the response leaves it out
     */
    public function getPriceGross(): ?float
    {
        return $this->priceGross;
    }

    /**
     * @param float|null $priceGross
     *
     * @return static
     */
    public function setPriceGross(?float $priceGross): static
    {
        $this->priceGross = $priceGross;

        return $this;
    }
}
