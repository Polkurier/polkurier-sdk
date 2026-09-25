<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies a single order.
 */
readonly class OrderIdMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NO = 'orderno';
    /**-@#*/

    /**
     * @param string $orderNo
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NO)]
        #[Validate\NotBlank]
        #[Validate\OrderNumber]
        private string $orderNo,
    ) {}

    /**
     * @return string
     */
    public function getOrderNo(): string
    {
        return $this->orderNo;
    }
}
