<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Response\BasePack;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One parcel as reported on an order, with what the carrier charged for it.
 *
 * To send parcel details, use {@see \Polkurier\Webservice\Request\Order\PackMessage}. The
 * carrier works out both prices from the dimensions given, rounded to its own increments, so they
 * may be based on larger figures than you sent.
 */
readonly class Pack extends BasePack
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_PRICE_NET = 'price_net';
    public const string FIELD_PRICE_GROSS = 'price_gross';
    /**-@#*/

    /**
     * @param int $length
     * @param int $width
     * @param int $height
     * @param float $weight
     * @param PackTypeEnum $type
     * @param int $amount
     * @param float|null $priceNet
     * @param float|null $priceGross
     */
    public function __construct(
        int $length,
        int $width,
        int $height,
        float $weight,
        PackTypeEnum $type = PackTypeEnum::Standard,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        private int $amount = 1,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_NET)]
        private ?float $priceNet = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_PRICE_GROSS)]
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
     * @return float|null
     */
    public function getPriceNet(): ?float
    {
        return $this->priceNet;
    }

    /**
     * @return float|null
     */
    public function getPriceGross(): ?float
    {
        return $this->priceGross;
    }
}
