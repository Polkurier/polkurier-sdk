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
 * Names one document attached to an order.
 */
readonly class GetOrderFileMessage implements MessageInterface
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
        private string $orderNo,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        #[Validate\NotBlank]
        private string $filename,
    ) {}

    /**
     * @return string
     */
    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
