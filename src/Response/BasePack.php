<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Dimensions and weight of one parcel, as the API reports them.
 *
 * To send them, use {@see \Polkurier\Webservice\Request\Common\BasePackMessage} or one of its
 * subclasses. Lengths are whole centimetres and the weight is in kilograms. Each carrier has its own
 * limits on both, and `available_carriers` reports them.
 */
readonly class BasePack implements MessageInterface
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
     * @param int $length
     * @param int $width
     * @param int $height
     * @param float $weight
     * @param PackTypeEnum $type
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_LENGTH)]
        private int $length,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_WIDTH)]
        private int $width,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_HEIGHT)]
        private int $height,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_WEIGHT)]
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
