<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\BankAccount;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\Common\TwoFactorFieldsTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Registers or updates a bank account.
 *
 * Pass an id to update that account, or leave it out to register a new one. Setting `default`
 * makes this the default account and removes the flag from the previous one.
 *
 * While `get_account_info` reports the bank-account PIN lock on, every save needs the access PIN.
 * Otherwise registering, or changing the number or default flag, needs a confirmed two-factor
 * token; renaming needs none. Both are optional here.
 */
readonly class SaveMessage implements MessageInterface
{
    use TwoFactorFieldsTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_DEFAULT = 'default';
    public const string FIELD_ACCESS_PIN = 'access_pin';
    /**-@#*/

    /**
     * @param string $name the customer's own label for the account
     * @param string $number a Polish account number, as an IBAN or the bare national number
     * @param string|null $id the account to update, or null to register a new one
     * @param bool $default make this the default account
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
     * The account's security PIN, required only while the bank-account lock is engaged.
     *
     * @return string|null
     */
    public function getAccessPin(): ?string
    {
        return $this->accessPin;
    }
}
