<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\BankAccount;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Registers or updates a bank account for cash-on-delivery settlement.
 *
 * Passing an id updates that account; leaving it out registers a new one. Setting `default` makes
 * it the account money is settled to, which un-defaults whichever one held that before.
 *
 * Carries two-factor fields because the API may refuse the change without a confirmed token, and
 * an access PIN because it may additionally be refused while the account's bank-account lock is
 * engaged: redirecting where money is paid is treated as a sensitive operation. Which of them a
 * given account needs is reported by `get_account_info` and `get_system_info`, so neither is
 * required here.
 */
readonly class SaveMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_DEFAULT = 'default';
    public const string FIELD_ACCESS_PIN = 'access_pin';
    public const string FIELD_TWO_FACTOR_TOKEN = 'two_factor_token';
    public const string FIELD_TWO_FACTOR_CODE = 'two_factor_code';
    /**-@#*/

    /**
     * @param string $name the customer's own label for the account
     * @param string $number the account number, as an IBAN or a bare national number
     * @param string|null $id the account to update; null registers a new one
     * @param bool $default settle cash on delivery to this account
     * @param string|null $twoFactorToken
     * @param string|null $twoFactorCode
     * @param string|null $accessPin required only while the bank-account PIN lock is engaged
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        #[Validate\NotBlank]
        private string $name,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[Validate\NotBlank]
        #[Validate\Iban]
        private string $number,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_TOKEN)]
        #[OmitWhenEmpty]
        private ?string $twoFactorToken = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE)]
        #[OmitWhenEmpty]
        private ?string $twoFactorCode = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ACCESS_PIN)]
        #[OmitWhenEmpty]
        private ?string $accessPin = null,
    ) {}

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @return string|null
     */
    public function getTwoFactorToken(): ?string
    {
        return $this->twoFactorToken;
    }

    /**
     * @return string|null
     */
    public function getTwoFactorCode(): ?string
    {
        return $this->twoFactorCode;
    }

    /**
     * The account's security PIN, required only while the bank-account lock is engaged.
     *
     * @return string|null
     */
    public function getAccessPin(): ?string
    {
        return $this->accessPin;
    }
}
