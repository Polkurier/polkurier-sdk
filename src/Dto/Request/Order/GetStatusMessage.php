<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies an order to fetch the status of.
 */
final readonly class GetStatusMessage extends OrderIdMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TRACKING = 'tracking';
    /**-@#*/

    /**
     * @param string $orderNo
     * @param bool $tracking request the carrier's scan history as well as the current status
     */
    public function __construct(
        string $orderNo,

        /**
         * Off by default. Turning it on makes the call slower.
         *
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING)]
        public bool $tracking = false,
    ) {
        parent::__construct($orderNo);
    }
}
