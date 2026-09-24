<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Sets, or clears, the default drop-off point for one carrier.
 *
 * Only carriers that actually operate points accept this — parcel lockers, post offices, Paczka w
 * Ruchu, DPD, FedEx, DHL and UPS Access Points. Sub-variants of those carriers are normalised onto
 * the parent, so setting the point for the InPost courier variant sets it for InPost lockers.
 *
 * The point id is validated against that carrier's network, so a code belonging to a different
 * carrier is rejected rather than stored. Sending an empty point clears the default instead.
 */
readonly class ChangeDefaultSenderPointMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_POINT = 'point';
    /**-@#*/

    /**
     * @param string $courier carrier code; must be one that operates pickup points
     * @param string $point point id, or an empty string to clear the default
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[Validate\NotBlank]
        private string $courier,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POINT)]
        private string $point = '',
    ) {}

    /**
     * @return string
     */
    public function getCourier(): string
    {
        return $this->courier;
    }

    /**
     * @return string
     */
    public function getPoint(): string
    {
        return $this->point;
    }
}
