<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Request\Common\BasePackMessage;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One parcel on an order being priced or placed.
 *
 * The amount is a multiplier for identical parcels: three boxes of the same size go as one entry
 * with an amount of three rather than three entries. Carriers that price per consignment rather
 * than per parcel still charge once, so the two are not interchangeable.
 */
readonly class PackMessage extends BasePackMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AMOUNT = 'amount';
    /**-@#*/

    /**
     * @param int $length in whole centimetres
     * @param int $width in whole centimetres
     * @param int $height in whole centimetres
     * @param float $weight in kilograms
     * @param PackTypeEnum $type the shape the carrier prices it as
     * @param int $amount how many identical parcels of these dimensions
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
        #[Validate\Positive]
        private int $amount = 1,
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
}
