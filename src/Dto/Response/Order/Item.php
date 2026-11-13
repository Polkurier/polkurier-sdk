<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\OrderItemTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One charge line on an order: carriage of a parcel, an additional service, or the discount, which
 * comes as a line with negative amounts.
 *
 * Returned when the order list asks for items or is filtered by order number, and in webhook
 * events.
 */
final readonly class Item implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_PRICE = 'price_net';
    public const string FIELD_SERVICE_ID = 'service_id';
    public const string FIELD_VALUE_NET = 'value_nett';
    public const string FIELD_VALUE_GROSS = 'value_gross';
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param string $description
     * @param int $amount
     * @param float $price
     * @param float $valueNet
     * @param float $valueGross
     * @param OrderItemTypeEnum|null $type
     * @param string|null $serviceId
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        public int $amount,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE)]
        public float $price,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_NET)]
        public float $valueNet,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_GROSS)]
        public float $valueGross,

        /**
         * @var OrderItemTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?OrderItemTypeEnum $type,

        /**
         * Identifier of the additional service this line charges for.
         *
         * Null on carriage and discount lines, and also on service lines not tied to a specific
         * service. To tell the kinds apart, use {@see self::$type}.
         *
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SERVICE_ID)]
        #[OmitWhenEmpty]
        public ?string $serviceId = null,
    ) {}
}
