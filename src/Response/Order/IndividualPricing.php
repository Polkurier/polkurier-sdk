<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An individually negotiated quote and its acceptance state.
 *
 * Only present on freight orders, which are priced by offer rather than from a tariff.
 */
class IndividualPricing implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_OFFER_SENT_DATE = 'offer_sent_date';
    public const string FIELD_OFFER_VALID_DATE = 'offer_valid_date';
    public const string FIELD_CUSTOMER_CONFIRMED_DATE = 'customer_confirmed_date';
    public const string FIELD_CUSTOMER_REMARKS = 'customer_remarks';
    /**-@#*/

    /**
     * @param \DateTimeInterface $offerSentDate
     * @param \DateTimeInterface $offerValidDate
     * @param \DateTimeInterface $customerConfirmedDate
     * @param string $customerRemarks
     */
    public function __construct(
        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_OFFER_SENT_DATE)]
        private \DateTimeInterface $offerSentDate,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_OFFER_VALID_DATE)]
        private \DateTimeInterface $offerValidDate,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_CUSTOMER_CONFIRMED_DATE)]
        private \DateTimeInterface $customerConfirmedDate,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CUSTOMER_REMARKS)]
        private string $customerRemarks,
    ) {}

    /**
     * @return \DateTimeInterface
     */
    public function getOfferSentDate(): \DateTimeInterface
    {
        return $this->offerSentDate;
    }

    /**
     * @param \DateTimeInterface $offerSentDate
     *
     * @return void
     */
    public function setOfferSentDate(\DateTimeInterface $offerSentDate): void
    {
        $this->offerSentDate = $offerSentDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getOfferValidDate(): \DateTimeInterface
    {
        return $this->offerValidDate;
    }

    /**
     * @param \DateTimeInterface $offerValidDate
     *
     * @return void
     */
    public function setOfferValidDate(\DateTimeInterface $offerValidDate): void
    {
        $this->offerValidDate = $offerValidDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCustomerConfirmedDate(): \DateTimeInterface
    {
        return $this->customerConfirmedDate;
    }

    /**
     * @param \DateTimeInterface $customerConfirmedDate
     *
     * @return void
     */
    public function setCustomerConfirmedDate(\DateTimeInterface $customerConfirmedDate): void
    {
        $this->customerConfirmedDate = $customerConfirmedDate;
    }

    /**
     * @return string
     */
    public function getCustomerRemarks(): string
    {
        return $this->customerRemarks;
    }

    /**
     * @param string $customerRemarks
     *
     * @return void
     */
    public function setCustomerRemarks(string $customerRemarks): void
    {
        $this->customerRemarks = $customerRemarks;
    }
}
