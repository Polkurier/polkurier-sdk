<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The additional services a carrier reports in `available_carriers`.
 *
 * Each getter is named after the {@see \Polkurier\Webservice\Enum\Order\AdditionalServiceEnum} case
 * to order for that service.
 */
final readonly class CourierService implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ROD = 'ROD';
    public const string FIELD_COVER_ADDRESS_SENDER = 'COVER_ADDRESS_SENDER';
    public const string FIELD_BRINGING_DELIVERED_PARCEL = 'BRINGING_DELIVERED_PARCEL';
    public const string FIELD_COURIER_WITH_LABEL = 'COURIER_WITH_LABEL';
    public const string FIELD_SATURDAY_DELIVERY = 'SATURDAY_DELIVERY';
    public const string FIELD_PHONE_NOTIFICATION_RECIPIENT = 'PHONE_NOTIFICATION_RECIPIENT';
    public const string FIELD_TIRES = 'TIRES';
    public const string FIELD_HAND_DELIVERY = 'HAND_DELIVERY';
    public const string FIELD_END_OF_WEEK_COLLECTION = 'END_OF_WEEK_COLLECTION';
    public const string FIELD_LABELLESS = 'LABELLESS';
    public const string FIELD_HANDLE_WITH_CARE = 'HANDLE_WITH_CARE';
    public const string FIELD_DELIVERY_TO_TIME = 'DELIVERY_TO_TIME';
    /**-@#*/

    /**
     * @param Property|null $returnOfDocuments
     * @param Property|null $coverSenderAddress
     * @param Property|null $carryIn
     * @param Property|null $courierBringsLabel
     * @param Property|null $saturdayDelivery
     * @param Property|null $phoneCallBeforeDelivery
     * @param Property|null $tires
     * @param Property|null $handDelivery
     * @param Property|null $weekendDelivery
     * @param Property|null $labelless
     * @param Property|null $handleWithCare
     * @param Property|null $deliveryByTime
     */
    public function __construct(
        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_ROD)]
        public ?Property $returnOfDocuments = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS_SENDER)]
        public ?Property $coverSenderAddress = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_BRINGING_DELIVERED_PARCEL)]
        public ?Property $carryIn = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_WITH_LABEL)]
        public ?Property $courierBringsLabel = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_SATURDAY_DELIVERY)]
        public ?Property $saturdayDelivery = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_NOTIFICATION_RECIPIENT)]
        public ?Property $phoneCallBeforeDelivery = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_TIRES)]
        public ?Property $tires = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_HAND_DELIVERY)]
        public ?Property $handDelivery = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_END_OF_WEEK_COLLECTION)]
        public ?Property $weekendDelivery = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_LABELLESS)]
        public ?Property $labelless = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_HANDLE_WITH_CARE)]
        public ?Property $handleWithCare = null,

        /**
         * @var Property|null null if the carrier doesn't list this service
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERY_TO_TIME)]
        public ?Property $deliveryByTime = null,
    ) {}
}
