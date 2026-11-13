<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order\PackMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The shipment to ask about when checking whether a collective pickup can be booked.
 *
 * Availability is decided per shipment: by the carrier and the parcels, not by the account alone.
 * Describe the shipment the way you would on `create_order`.
 *
 * Unlike `create_order`, the sender postcode and the shipment type are sent in camelCase.
 */
final readonly class MultiPickupAvailabilityRequestMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_PACKS = 'packs';
    public const string FIELD_SENDER_POST_CODE = 'senderPostCode';
    public const string FIELD_SHIPMENT_TYPE = 'shipmentType';
    /**-@#*/

    /**
     * @param string $courier carrier code, as returned by `available_carriers`
     * @param PackMessage[] $packs the parcels, at least one
     * @param string|null $senderPostCode postcode the shipment is sent from
     * @param ShipmentTypeEnum|null $shipmentType
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        public string $courier,

        /**
         * @var PackMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[Validate\NotEmptyList]
        #[Validate\Valid]
        #[Validate\Each(Validate\OfType::class, PackMessage::class)]
        public array $packs,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SENDER_POST_CODE)]
        #[OmitWhenEmpty]
        public ?string $senderPostCode = null,

        /**
         * @var ShipmentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SHIPMENT_TYPE)]
        #[OmitWhenEmpty]
        public ?ShipmentTypeEnum $shipmentType = null,
    ) {}
}
