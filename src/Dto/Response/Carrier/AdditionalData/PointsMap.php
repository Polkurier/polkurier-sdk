<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\PointRequirementEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a carrier works with pickup points, and which point providers its map shows.
 *
 * @api
 */
final readonly class PointsMap implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE_PROVIDERS = 'available_providers';
    public const string FIELD_SENDER_POINT = 'sender_point';
    public const string FIELD_RECIPIENT_POINT = 'recipient_point';
    /**-@#*/

    /**
     * @param string[] $availableProviders
     * @param PointRequirementEnum|null $senderPoint
     * @param PointRequirementEnum|null $recipientPoint
     */
    public function __construct(
        /**
         * The carrier codes to pass, all together, to
         * {@see \Polkurier\Webservice\Api\Service\CourierServiceInterface::findCourierPoints()} for
         * this carrier's points.
         *
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE_PROVIDERS)]
        public array $availableProviders = [],

        /**
         * @var PointRequirementEnum|null null if the carrier's entry leaves it out, or for a value
         *         the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_SENDER_POINT)]
        public ?PointRequirementEnum $senderPoint = null,

        /**
         * @var PointRequirementEnum|null null if the carrier's entry leaves it out, or for a value
         *         the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT_POINT)]
        public ?PointRequirementEnum $recipientPoint = null,
    ) {}
}
