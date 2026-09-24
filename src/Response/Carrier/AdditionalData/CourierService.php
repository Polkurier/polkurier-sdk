<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class CourierService implements MessageInterface
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
     * @param Property|null $rod
     * @param Property|null $coverAddressSender
     * @param Property|null $bringingDeliveredParcel
     * @param Property|null $courierWithLabel
     * @param Property|null $saturdayDelivery
     * @param Property|null $phoneNotificationRecipient
     * @param Property|null $tires
     * @param Property|null $handDelivery
     * @param Property|null $endOfWeekCollection
     * @param Property|null $labelless
     * @param Property|null $handleWithCare
     * @param Property|null $deliveryToTime
     */
    public function __construct(
        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_ROD)]
        private ?Property $rod = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS_SENDER)]
        private ?Property $coverAddressSender = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_BRINGING_DELIVERED_PARCEL)]
        private ?Property $bringingDeliveredParcel = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER_WITH_LABEL)]
        private ?Property $courierWithLabel = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_SATURDAY_DELIVERY)]
        private ?Property $saturdayDelivery = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_NOTIFICATION_RECIPIENT)]
        private ?Property $phoneNotificationRecipient = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_TIRES)]
        private ?Property $tires = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_HAND_DELIVERY)]
        private ?Property $handDelivery = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_END_OF_WEEK_COLLECTION)]
        private ?Property $endOfWeekCollection = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_LABELLESS)]
        private ?Property $labelless = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_HANDLE_WITH_CARE)]
        private ?Property $handleWithCare = null,

        /**
         * @var Property|null
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERY_TO_TIME)]
        private ?Property $deliveryToTime = null,
    ) {}

    /**
     * @return Property|null
     */
    public function getROD(): ?Property
    {
        return $this->rod;
    }

    /**
     * @return Property|null
     */
    public function getCoverAddressSender(): ?Property
    {
        return $this->coverAddressSender;
    }

    /**
     * @return Property|null
     */
    public function getBringingDeliveredParcel(): ?Property
    {
        return $this->bringingDeliveredParcel;
    }

    /**
     * @return Property|null
     */
    public function getCourierWithLabel(): ?Property
    {
        return $this->courierWithLabel;
    }

    /**
     * @return Property|null
     */
    public function getSaturdayDelivery(): ?Property
    {
        return $this->saturdayDelivery;
    }

    /**
     * @return Property|null
     */
    public function getPhoneNotificationRecipient(): ?Property
    {
        return $this->phoneNotificationRecipient;
    }

    /**
     * @return Property|null
     */
    public function getTires(): ?Property
    {
        return $this->tires;
    }

    /**
     * @return Property|null
     */
    public function getHandDelivery(): ?Property
    {
        return $this->handDelivery;
    }

    /**
     * @return Property|null
     */
    public function getEndOfWeekCollection(): ?Property
    {
        return $this->endOfWeekCollection;
    }

    /**
     * @return Property|null
     */
    public function getLabelless(): ?Property
    {
        return $this->labelless;
    }

    /**
     * @return Property|null
     */
    public function getHandleWithCare(): ?Property
    {
        return $this->handleWithCare;
    }

    /**
     * @return Property|null
     */
    public function getDeliveryToTime(): ?Property
    {
        return $this->deliveryToTime;
    }
}
