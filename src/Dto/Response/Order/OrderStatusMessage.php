<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Current delivery state of one order, optionally with its scan history.
 *
 * `get_status` returns an API error for a cancelled order instead of a cancelled status, so a
 * successful response always describes a live order.
 *
 * The dates differ from the ones `get_orders` uses: the status timestamp has no seconds, the
 * delivery date has no time, and the scan timestamps in the history are full datetimes.
 *
 * @api
 */
final readonly class OrderStatusMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param OrderStatusCodeEnum|null $statusCode
     * @param \DateTimeInterface|null $statusDate
     * @param \DateTimeInterface|null $deliveredDate date only, with no time component
     * @param string $url public track-and-trace URL, empty while the order has no waybill
     * @param TrackingEventMessage[] $tracking scan history, empty unless asked for, or when the
     *        carrier has not reported anything yet
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public string $status = '',

        /**
         * @var OrderStatusCodeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        public ?OrderStatusCodeEnum $statusCode = null,

        /**
         * @var \DateTimeInterface|null null if the response leaves it out, as it does while the order has no waybill
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i'])]
        public ?\DateTimeInterface $statusDate = null,

        /**
         * @var \DateTimeInterface|null the day, at midnight. Null until the order is delivered
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERED_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        public ?\DateTimeInterface $deliveredDate = null,

        #[Serializer\SerializedName(self::FIELD_URL)]
        public string $url = '',

        /**
         * @var TrackingEventMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_TRACKING)]
        public array $tracking = [],

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
