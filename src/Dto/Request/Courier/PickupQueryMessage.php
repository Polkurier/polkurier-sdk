<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A message naming a carrier and the route of a collection: from and to which postcode, and what.
 *
 * @api
 */
abstract readonly class PickupQueryMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_SHIP_FROM = 'shipfrom';
    public const string FIELD_SHIP_TO = 'shipto';
    public const string FIELD_PARCEL = 'parcel';
    /**-@#*/

    /**
     * @param string $courier
     * @param string $shipFrom
     * @param string|null $shipTo
     * @param ShipmentTypeEnum|null $parcel
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        public string $courier,

        #[Serializer\SerializedName(self::FIELD_SHIP_FROM)]
        #[Validate\NotBlank]
        #[Validate\Postcode]
        public string $shipFrom,

        #[Serializer\SerializedName(self::FIELD_SHIP_TO)]
        #[OmitWhenEmpty]
        #[Validate\Postcode]
        public ?string $shipTo = null,

        #[Serializer\SerializedName(self::FIELD_PARCEL)]
        #[OmitWhenEmpty]
        public ?ShipmentTypeEnum $parcel = null,
    ) {}
}
