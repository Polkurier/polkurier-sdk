<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Result of `cancel_order`.
 */
readonly class CancellationMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CANCELLATION = 'cancellation';
    /**-@#*/

    /**
     * @param bool $cancellation whether the order was cancelled
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_CANCELLATION)]
        private bool $cancellation = false,
    ) {}

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancellation;
    }
}
