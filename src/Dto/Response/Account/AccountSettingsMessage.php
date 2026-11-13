<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Preferences the customer has set in the panel.
 *
 * These are defaults for the order form. The API does not enforce them.
 */
final readonly class AccountSettingsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_DELIVERY_PICKUP_MYSELF = 'user_delivery_pickup_myself';
    public const string FIELD_COVER_ADDRESS_SENDER_CHECKED = 'cover_address_sender_checked';
    /**-@#*/

    /**
     * @param bool $deliveryPickupMyself the customer usually hands parcels over instead of
     *                                   booking a collection
     * @param bool $coverAddressSenderChecked the alternative return address is normally enabled
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DELIVERY_PICKUP_MYSELF)]
        private bool $deliveryPickupMyself = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS_SENDER_CHECKED)]
        private bool $coverAddressSenderChecked = false,
    ) {}

    /**
     * @return bool
     */
    public function isDeliveryPickupMyself(): bool
    {
        return $this->deliveryPickupMyself;
    }

    /**
     * @return bool
     */
    public function isCoverAddressSenderChecked(): bool
    {
        return $this->coverAddressSenderChecked;
    }
}
