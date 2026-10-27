<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Webhook;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Webhook\OrderWebhookTypeEnum;
use Polkurier\Webservice\Response\Order;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An order event Polkurier posts to the webhook address set in the Polkurier panel.
 *
 * Read it with {@see \Polkurier\Webservice\Webhook\OrderWebhookReader}. The order is in the shape
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrders()} gives it, with
 * parcels and items and without files.
 */
readonly class OrderWebhookMessage implements MessageInterface
{
    /**+@#
     * Form field names
     */
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DATA = 'data';
    public const string FIELD_HASH = 'hash';
    /**-@#*/

    /**
     * @param ?OrderWebhookTypeEnum $type what happened, null when the SDK doesn't know the type
     * @param Order $order the order, as it was when the event was sent
     * @param string $hash the event's checksum
     */
    public function __construct(
        /**
         * @var ?OrderWebhookTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?OrderWebhookTypeEnum $type,

        /**
         * @var Order
         */
        #[Serializer\SerializedName(self::FIELD_DATA)]
        private Order $order,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        private string $hash = '',
    ) {}

    /**
     * @return ?OrderWebhookTypeEnum null when the SDK doesn't know the type
     */
    public function getType(): ?OrderWebhookTypeEnum
    {
        return $this->type;
    }

    /**
     * @return Order the order, as it was when the event was sent
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return string the event's checksum
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
