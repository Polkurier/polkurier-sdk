<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies an order to fetch the status of.
 *
 * @see GetStatusMessage
 */
readonly class GetStatusMessage extends OrderIdMessage
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
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING)]
        private bool $tracking = false,
    ) {
        parent::__construct($orderNo);
    }

    /**
     * Whether to also fetch the carrier's scan history.
     *
     * Off by default. Turning it on makes the call slower. Leave it off if you only need the
     * current status.
     *
     * @return bool
     */
    public function isTracking(): bool
    {
        return $this->tracking;
    }
}
