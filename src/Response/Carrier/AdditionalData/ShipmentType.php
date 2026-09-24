<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class ShipmentType implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ENVELOPE = 'envelope';
    public const string FIELD_BOX = 'box';
    public const string FIELD_PALETTE = 'palette';
    /**-@#*/

    /**
     * @param Property $envelope
     * @param Property $box
     * @param Property $palette
     */
    public function __construct(
        /**
         * @var Property
         */
        #[Serializer\SerializedName(self::FIELD_ENVELOPE)]
        private Property $envelope,

        /**
         * @var Property
         */
        #[Serializer\SerializedName(self::FIELD_BOX)]
        private Property $box,

        /**
         * @var Property
         */
        #[Serializer\SerializedName(self::FIELD_PALETTE)]
        private Property $palette,
    ) {}

    /**
     * @return Property
     */
    public function getEnvelope(): Property
    {
        return $this->envelope;
    }

    /**
     * @return Property
     */
    public function getBox(): Property
    {
        return $this->box;
    }

    /**
     * @return Property
     */
    public function getPalette(): Property
    {
        return $this->palette;
    }
}
