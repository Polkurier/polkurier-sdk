<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies one document attached to an order.
 *
 * @api
 */
final readonly class GetOrderFileMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NO = 'orderno';
    public const string FIELD_FILENAME = 'filename';
    /**-@#*/

    /**
     * @param string $orderNo the order number
     * @param string $filename the filename, as the order listing reports it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NO)]
        #[Validate\NotBlank]
        #[Validate\OrderNumber]
        public string $orderNo,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        #[Validate\NotBlank]
        public string $filename,
    ) {}
}
