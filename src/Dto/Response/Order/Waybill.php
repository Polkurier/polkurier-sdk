<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A tracking number assigned to an order.
 *
 * An order can have several. The primary one is marked `is_default`, and the order's status and
 * tracking link follow it.
 */
final readonly class Waybill implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_DEFAULT = 'is_default';
    /**-@#*/

    /**
     * @param string $number
     * @param bool $default
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        public string $number,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        public bool $default = false,
    ) {}
}
