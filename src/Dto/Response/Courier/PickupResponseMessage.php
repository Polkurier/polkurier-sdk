<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a carrier can collect on the requested day, and the windows it offers.
 *
 * Carriers that do not report time windows return an empty list even when they accept the date.
 */
final readonly class PickupResponseMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    public const string FIELD_TIME = 'time';
    /**-@#*/

    /**
     * @param bool $pickupDate whether the carrier can collect on the requested date
     * @param TimeIntervalMessage[] $time pickup windows, empty for couriers that do not
     *                                             report them
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        public bool $pickupDate = false,

        /**
         * @var TimeIntervalMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TIME)]
        public array $time = [],

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
