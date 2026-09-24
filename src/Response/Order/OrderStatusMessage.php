<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Current delivery state of one order, with its tracking history.
 *
 * Note that `get_status` rejects cancelled orders with an API error rather than reporting a
 * cancelled status, so a successful response always describes a live order.
 *
 * Three date formats appear in one response, and they are not the ones `get_orders` uses: the
 * status timestamp has no seconds, the delivery date has no time at all, and the scan timestamps
 * inside the history are full datetimes. The formats are pinned per field for that reason.
 */
readonly class OrderStatusMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_STATUS_DATE = 'status_date';
    public const string FIELD_DELIVERED_DATE = 'delivered_date';
    public const string FIELD_URL = 'url';
    public const string FIELD_TRACKING = 'tracking';
    /**-@#*/

    /**
     * @param string $status
     * @param string $statusCode
     * @param \DateTimeInterface|null $statusDate
     * @param \DateTimeInterface|null $deliveredDate date only, with no time component
     * @param string $url public track-and-trace URL
     * @param TrackingEventMessage[] $tracking scan history, oldest first; empty when the carrier
     *                                         has not reported anything yet
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private string $status = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        private string $statusCode = '',

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $statusDate = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERED_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $deliveredDate = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_URL)]
        private string $url = '',

        /**
         * @var TrackingEventMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING)]
        private array $tracking = [],
    ) {}

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
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStatusDate(): ?\DateTimeInterface
    {
        return $this->statusDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDeliveredDate(): ?\DateTimeInterface
    {
        return $this->deliveredDate;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return TrackingEventMessage[]
     */
    public function getTracking(): array
    {
        return $this->tracking;
    }
}
