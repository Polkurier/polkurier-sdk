<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\Common\TwoFactorFieldsTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a telephone number, optionally making it the account's default.
 *
 * Formatting such as spaces or dashes does not matter. An invalid number is rejected with an error
 * on the `number` field.
 *
 * Making the number the default requires a confirmed two-factor token registered under
 * CHANGE_DEFAULT_PHONE_NUMBER, and a Polish mobile number. Adding a non-default number needs no
 * confirmation.
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
    use TwoFactorFieldsTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DEFAULT = 'default';
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
}
