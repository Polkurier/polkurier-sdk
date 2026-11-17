<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The payload for `create_order` and `order_valuation_v2`.
 *
 * Both methods take the same message, but a payload that prices successfully can still be rejected
 * when you place the order.
 *
 * @api
 */
final readonly class CreateOrderMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COUPON_CODE = 'coupon_code';
    /**-@#*/

    /**
     * @param Order $order the order to place. Its fields are sent at the top level
     * @param string|null $couponCode
     */
    public function __construct(
        /**
         * @var Order
         */
        #[InlineFields]
        public Order $order,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COUPON_CODE)]
        #[OmitWhenEmpty]
        public ?string $couponCode = null,
    ) {}
}
