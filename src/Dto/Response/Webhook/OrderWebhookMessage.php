<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Webhook;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order\Order;
use Polkurier\Webservice\Enum\Webhook\OrderWebhookTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An order event Polkurier posts to the webhook address set in the Polkurier panel.
 *
 * Read it with {@see \Polkurier\Webservice\Webhook\OrderWebhookReader}. The order is in the shape
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrders()} gives it, with
 * parcels and items and without files.
 */
final readonly class OrderWebhookMessage implements MessageInterface
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
         * @var ?OrderWebhookTypeEnum null when the SDK doesn't know the type
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?OrderWebhookTypeEnum $type,

        /**
         * @var Order the order, as it was when the event was sent
         */
        #[Serializer\SerializedName(self::FIELD_DATA)]
        public Order $order,

        /**
         * @var string the event's checksum
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        public string $hash = '',
    ) {}
}
