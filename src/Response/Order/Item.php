<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\OrderItemTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One charge line on an order: either carriage of a parcel or an additional service.
 *
 * Returned only when a listing asks for items.
 */
class Item implements MessageInterface
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
     * @param OrderItemTypeEnum $type
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
         * @var OrderItemTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private OrderItemTypeEnum $type,

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
     * @param string $description
     *
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     *
     * @return void
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return void
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getValueNet(): float
    {
        return $this->valueNet;
    }

    /**
     * @param float $valueNet
     *
     * @return void
     */
    public function setValueNet(float $valueNet): void
    {
        $this->valueNet = $valueNet;
    }

    /**
     * @return float
     */
    public function getValueGross(): float
    {
        return $this->valueGross;
    }

    /**
     * @param float $valueGross
     *
     * @return void
     */
    public function setValueGross(float $valueGross): void
    {
        $this->valueGross = $valueGross;
    }

    /**
     * @return OrderItemTypeEnum
     */
    public function getType(): OrderItemTypeEnum
    {
        return $this->type;
    }

    /**
     * @param OrderItemTypeEnum $type
     *
     * @return void
     */
    public function setType(OrderItemTypeEnum $type): void
    {
        $this->type = $type;
    }

    /**
     * Identifier of the additional service this line charges for.
     *
     * Null on carriage lines, and also on service lines the API does not attribute to a specific
     * service, so it cannot be used to tell the two apart — read {@see self::getType()} for that.
     *
     * @return string|null
     */
    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }
}
