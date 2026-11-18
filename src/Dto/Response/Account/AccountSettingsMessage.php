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
 *
 * @api
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
     *        booking a collection
     * @param bool $coverAddressSenderChecked the alternative return address is normally enabled
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_DELIVERY_PICKUP_MYSELF)]
        public bool $deliveryPickupMyself = false,

        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS_SENDER_CHECKED)]
        public bool $coverAddressSenderChecked = false,
    ) {}
}
