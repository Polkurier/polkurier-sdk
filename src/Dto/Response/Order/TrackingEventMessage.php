<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A single scan event in a shipment's tracking history.
 *
 * @api
 */
final readonly class TrackingEventMessage implements MessageInterface
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
     * @param string $location where the scan happened, empty when the carrier doesn't say
     * @param string $description
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null null if the event comes without a date
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
        public ?\DateTimeInterface $statusDate = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        public string $statusCode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public string $status = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING_STATUS)]
        public string $trackingStatus = '',

        /**
         * @var string empty when the API sends none
         */
        #[Serializer\SerializedName(self::FIELD_LOCATION)]
        public string $location = '',

        /**
         * @var string empty when the API sends none
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',
    ) {}
}
