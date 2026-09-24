<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Symfony\Component\Serializer\Attribute as Serializer;

/**
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
      * Whether to ask for the carrier's scan history alongside the current status.
      *
      * Off by default. Turning it on makes the API query the carrier's tracking service, which is
      * slower than reading the order, so leave it off when only the current state is needed.
      *
     * @return bool
     */
    public function isTracking(): bool
    {
        return $this->tracking;
    }
}
