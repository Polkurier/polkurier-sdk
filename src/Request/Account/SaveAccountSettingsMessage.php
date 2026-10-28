<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Changes the account's order-form defaults.
 *
 * This is a partial update: every field is optional and only the fields you send are changed. If
 * any part fails, nothing is changed.
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
     * @param string|null $defaultSenderAddressId id of an address-book sender entry; an id that
     *        names none of the account's senders is ignored
     * @param string|null $defaultCourier carrier code, must be one the account can use
     * @param array<string, string>|null $defaultSenderPoint map of carrier code to default point.
     *        Carriers not in the map are left unchanged. Only InPost locker, Pocztex point, DPD
     *        point and UPS Access Point codes are applied, and the point isn't checked against the
     *        carrier's network
     *        ({@see \Polkurier\Webservice\Api\Service\AccountServiceInterface::changeDefaultSenderPoint()}
     *        checks it)
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
