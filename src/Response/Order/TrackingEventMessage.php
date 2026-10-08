<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A single scan event in a shipment's tracking history.
 */
readonly class TrackingEventMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS_DATE = 'status_date';
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_TRACKING_STATUS = 'tracking_status';
    public const string FIELD_LOCATION = 'location';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $statusDate
     * @param string $statusCode Polkurier's status code, e.g. "WP"
     * @param string $status Polkurier's status label
     * @param string $trackingStatus the carrier's own status name
     * @param string|null $location where the scan happened; null when the carrier doesn't say
     * @param string|null $description
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
        private ?\DateTimeInterface $statusDate = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        private string $statusCode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private string $status = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_STATUS)]
        private string $trackingStatus = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_LOCATION)]
        private ?string $location = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private ?string $description = '',
    ) {}

    /**
     * @return \DateTimeInterface|null null if the event comes without a date
     */
    public function getStatusDate(): ?\DateTimeInterface
    {
        return $this->statusDate;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getTrackingStatus(): string
    {
        return $this->trackingStatus;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location ?? '';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }
}
