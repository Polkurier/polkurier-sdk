<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The dimensions and weight of one parcel.
 *
 * Dimensions are in whole centimetres and the weight in kilograms. Carriers round both up to their
 * own increments, so a quote may be based on slightly larger figures.
 */
readonly class BasePackMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_LENGTH = 'length';
    public const string FIELD_WIDTH = 'width';
    public const string FIELD_HEIGHT = 'height';
    public const string FIELD_WEIGHT = 'weight';
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param int $length in whole centimetres
     * @param int $width in whole centimetres
     * @param int $height in whole centimetres
     * @param float $weight in kilograms
     * @param PackTypeEnum $type the shape the carrier prices it as
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_LENGTH)]
        #[Validate\Positive]
        private int $length,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_WIDTH)]
        #[Validate\Positive]
        private int $width,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_HEIGHT)]
        #[Validate\Positive]
        private int $height,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_WEIGHT)]
        #[Validate\Positive]
        private float $weight,

        /**
         * @var PackTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private PackTypeEnum $type = PackTypeEnum::Standard,
    ) {}

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @return PackTypeEnum
     */
    public function getType(): PackTypeEnum
    {
        return $this->type;
    }
}
