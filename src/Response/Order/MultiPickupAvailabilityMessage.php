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
 * Whether the account may order a single pickup covering several shipments.
 */
readonly class MultiPickupAvailabilityMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE = 'isMultiPickupAvailable';
    /**-@#*/

    /**
     * @param bool $available
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        private bool $available = false,
    ) {}

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }
}
