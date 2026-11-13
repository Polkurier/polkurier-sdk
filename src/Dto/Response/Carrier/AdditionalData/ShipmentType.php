<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Which shipment types a carrier accepts: envelope, box or pallet.
 */
final readonly class ShipmentType implements MessageInterface
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
         * @var Property|null null if the carrier reported nothing for this type
         */
        #[Serializer\SerializedName(self::FIELD_ENVELOPE)]
        public ?Property $envelope = null,

        /**
         * @var Property|null null if the carrier reported nothing for this type
         */
        #[Serializer\SerializedName(self::FIELD_BOX)]
        public ?Property $box = null,

        /**
         * @var Property|null null if the carrier reported nothing for this type
         */
        #[Serializer\SerializedName(self::FIELD_PALETTE)]
        public ?Property $palette = null,
    ) {}
}
