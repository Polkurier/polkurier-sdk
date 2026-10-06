<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Which shipment types a carrier accepts: envelope, box or pallet.
 */
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
     * @param Property|null $envelope
     * @param Property|null $box
     * @param Property|null $palette
     */
    public function __construct(
        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_ENVELOPE)]
        private ?Property $envelope = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_BOX)]
        private ?Property $box = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_PALETTE)]
        private ?Property $palette = null,
    ) {}

    /**
     * @return Property|null null if the carrier reported nothing for this type
     */
    public function getEnvelope(): ?Property
    {
        return $this->envelope;
    }

    /**
     * @return Property|null null if the carrier reported nothing for this type
     */
    public function getBox(): ?Property
    {
        return $this->box;
    }

    /**
     * @return Property|null null if the carrier reported nothing for this type
     */
    public function getPalette(): ?Property
    {
        return $this->palette;
    }
}
