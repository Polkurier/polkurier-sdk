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
class BasePack implements MessageInterface
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
     * @param int $length
     *
     * @return void
     */
    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return void
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return void
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     *
     * @return void
     */
    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return PackTypeEnum
     */
    public function getType(): PackTypeEnum
    {
        return $this->type;
    }

    /**
     * @param PackTypeEnum $type
     *
     * @return void
     */
    public function setType(PackTypeEnum $type): void
    {
        $this->type = $type;
    }
}
