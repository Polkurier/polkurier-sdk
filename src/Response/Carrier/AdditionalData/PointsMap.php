<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\PointRequirementEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class PointsMap implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE_PROVIDERS = 'available_providers';
    public const string FIELD_SENDER_POINT = 'sender_point';
    public const string FIELD_RECIPIENT_POINT = 'recipient_point';
    /**
     * -@#
     */

    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE_PROVIDERS)]
        private array $availableProviders,

        /**
         * @var PointRequirementEnum
         */
        #[Serializer\SerializedName(self::FIELD_SENDER_POINT)]
        private PointRequirementEnum $senderPoint,

        /**
         * @var PointRequirementEnum
         */
        #[Serializer\SerializedName(self::FIELD_RECIPIENT_POINT)]
        private PointRequirementEnum $recipientPoint,
    ) {}

    /**
     * @return string[]
     */
    public function getAvailableProviders(): array
    {
        return $this->availableProviders;
    }

    /**
     * @return PointRequirementEnum
     */
    public function getSenderPoint(): PointRequirementEnum
    {
        return $this->senderPoint;
    }

    /**
     * @return PointRequirementEnum
     */
    public function getRecipientPoint(): PointRequirementEnum
    {
        return $this->recipientPoint;
    }
}
