<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Courier;

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Asks whether a carrier can collect on one day, and in which time windows. Nothing is booked.
 */
readonly class PickupMessage extends GetPickupTimeMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PICKUP_DATE = 'pickupdate';
    /**-@#*/

    /**
     * @param string $courier
     * @param string $shipFrom
     * @param string|null $shipTo
     * @param ShipmentTypeEnum|null $parcel
     * @param \DateTimeInterface|null $pickupDate the day to check, today when null
     */
    public function __construct(
        string $courier,
        string $shipFrom,
        ?string $shipTo = null,
        ?ShipmentTypeEnum $parcel = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_PICKUP_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $pickupDate = null,
    ) {
        parent::__construct($courier, $shipFrom, $shipTo, $parcel);
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPickupDate(): ?\DateTimeInterface
    {
        return $this->pickupDate;
    }
}
