<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\OrderItemTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One charge line on an order: carriage of a parcel, or an additional service.
 *
 * Returned only when a listing asks for items.
 */
readonly class Item implements MessageInterface
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
        private string $description,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        private int $amount,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PRICE)]
        private float $price,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_NET)]
        private float $valueNet,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VALUE_GROSS)]
        private float $valueGross,

        /**
         * @var OrderItemTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?OrderItemTypeEnum $type,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SERVICE_ID)]
        #[OmitWhenEmpty]
        private ?string $serviceId = null,
    ) {}

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getValueNet(): float
    {
        return $this->valueNet;
    }

    /**
     * @return float
     */
    public function getValueGross(): float
    {
        return $this->valueGross;
    }

    /**
     * @return OrderItemTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?OrderItemTypeEnum
    {
        return $this->type;
    }

    /**
     * Identifier of the additional service this line charges for.
     *
     * Null on carriage lines, and also on service lines not tied to a specific service. To tell the
     * two kinds apart, use {@see self::getType()}.
     *
     * @return string|null
     */
    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }
}
