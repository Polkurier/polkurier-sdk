<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Sets, or clears, the default drop-off point for one carrier.
 *
 * The carrier codes accepted are `INPOST_PACZKOMAT`, `POCZTEX_PUNKT`, `PACZKA_W_RUCHU`,
 * `DPD_POINT`, `FEDEX_DTR`, `DHL_POINT` and `UPS_AP_POINT_POINT`.
 *
 * The point id must belong to that carrier's network. Send an empty point to clear the default.
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
     * @param string $courier one of the carrier codes listed above
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
