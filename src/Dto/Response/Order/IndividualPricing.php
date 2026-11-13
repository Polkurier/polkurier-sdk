<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An individually negotiated quote and its acceptance state.
 *
 * Present only on freight orders, which are priced by offer instead of from a tariff. The validity
 * and confirmation dates are null until the offer has them.
 */
final readonly class IndividualPricing implements MessageInterface
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
     * @param \DateTimeInterface|null $offerSentDate
     * @param \DateTimeInterface|null $offerValidDate
     * @param \DateTimeInterface|null $customerConfirmedDate
     * @param string $customerRemarks
     */
    public function __construct(
        /**
         * @var \DateTimeInterface|null null if the offer hasn't been sent yet
         */
        #[Serializer\SerializedName(self::FIELD_OFFER_SENT_DATE)]
        public ?\DateTimeInterface $offerSentDate,

        /**
         * @var \DateTimeInterface|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_OFFER_VALID_DATE)]
        public ?\DateTimeInterface $offerValidDate = null,

        /**
         * @var \DateTimeInterface|null null until the customer confirms the offer
         */
        #[Serializer\SerializedName(self::FIELD_CUSTOMER_CONFIRMED_DATE)]
        public ?\DateTimeInterface $customerConfirmedDate = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CUSTOMER_REMARKS)]
        public string $customerRemarks = '',
    ) {}
}
