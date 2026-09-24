<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Changes the account's order-form defaults.
 *
 * Every field is optional and only the ones present are touched, so this is a partial update
 * rather than a replacement. The whole thing runs in one transaction: if any part fails, none of
 * it is applied.
 */
readonly class SaveAccountSettingsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_DEFAULT_SENDER_ADDRESS_ID = 'default_sender_address_id';
    public const string FIELD_DEFAULT_COURIER = 'default_courier';
    public const string FIELD_DEFAULT_SENDER_POINT = 'default_sender_point';
    /**-@#*/

    /**
     * @param string|null $defaultSenderAddressId id of an address-book sender entry
     * @param string|null $defaultCourier carrier code; validated against what the account may use
     * @param array<string, string>|null $defaultSenderPoint default point per carrier code, as a
     *        map; carriers not listed are left alone
     */
    public function __construct(
        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_SENDER_ADDRESS_ID)]
        #[OmitWhenEmpty]
        private ?string $defaultSenderAddressId = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_COURIER)]
        #[OmitWhenEmpty]
        private ?string $defaultCourier = null,

        /**
         * @var array<string, string>|null
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_SENDER_POINT)]
        #[OmitWhenEmpty]
        private ?array $defaultSenderPoint = null,
    ) {}

    /**
     * @return string|null
     */
    public function getDefaultSenderAddressId(): ?string
    {
        return $this->defaultSenderAddressId;
    }

    /**
     * @return string|null
     */
    public function getDefaultCourier(): ?string
    {
        return $this->defaultCourier;
    }

    /**
     * @return array<string, string>|null
     */
    public function getDefaultSenderPoint(): ?array
    {
        return $this->defaultSenderPoint;
    }
}
