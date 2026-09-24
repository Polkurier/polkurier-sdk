<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a telephone number, optionally making it the account's default.
 *
 * The number is stripped to digits and validated, so formatting is irrelevant but a malformed
 * number is rejected naming the `number` field.
 *
 * Making it the default is the part that is guarded: that requires a confirmed two-factor token
 * registered under CHANGE_DEFAULT_PHONE_NUMBER. Adding a non-default number needs no confirmation.
 * The one exception is the first number on an account created without one, which is made default
 * automatically and skips the check.
 */
#[Validate\RequiredWhen(
    field: 'twoFactorToken',
    property: 'default',
    because: 'is required when making a number the default',
)]
#[Validate\RequiredWhen(
    field: 'twoFactorCode',
    property: 'default',
    because: 'is required when making a number the default',
)]
readonly class SavePhoneNumberMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DEFAULT = 'default';
    public const string FIELD_TWO_FACTOR_TOKEN = 'two_factor_token';
    public const string FIELD_TWO_FACTOR_CODE = 'two_factor_code';
    /**-@#*/

    /**
     * @param string $number
     * @param string $name the customer's own label for the number
     * @param bool $default make this the account's primary number
     * @param string|null $twoFactorToken required when $default is true
     * @param string|null $twoFactorCode required when $default is true
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        private string $number,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_TOKEN)]
        #[OmitWhenEmpty]
        private ?string $twoFactorToken = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE)]
        #[OmitWhenEmpty]
        private ?string $twoFactorCode = null,
    ) {}

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
}
