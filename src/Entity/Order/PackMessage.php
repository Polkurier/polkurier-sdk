<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Entity\Pack\BasePackMessage;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One parcel on an order being priced or placed.
 *
 * The amount is a multiplier for identical parcels. Send 3 boxes of the same size as one entry with
 * an amount of 3. Carriers that price per consignment still charge once for it, so this is not the
 * same as sending 3 separate entries.
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
