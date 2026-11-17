<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Common\TwoFactorFieldsInterface;
use Polkurier\Webservice\Entity\PhoneNumber;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a phone number, with the confirmation making it the default requires.
 *
 * Making the number the default requires a confirmed two-factor token registered under
 * CHANGE_DEFAULT_PHONE_NUMBER, and a Polish mobile number. Adding a non-default number needs no
 * confirmation.
 *
 * @api
 */
#[Validate\RequiredWhen(
    field: 'twoFactorToken',
    property: 'default',
    reason: 'is required when making a number the default',
)]
#[Validate\RequiredWhen(
    field: 'twoFactorCode',
    property: 'default',
    reason: 'is required when making a number the default',
)]
final readonly class SavePhoneNumberMessage implements MessageInterface, TwoFactorFieldsInterface
{

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PHONE_NUMBER = 'phone_number';
    /**-@#*/

    /**
     * @param PhoneNumber $phoneNumber the number to add. Its fields are sent at the top level
     * @param string|null $twoFactorToken required when the number becomes the default
     * @param string|null $twoFactorCode required when the number becomes the default
     */
    public function __construct(
        /**
         * @var PhoneNumber
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_NUMBER)]
        #[InlineFields]
        #[Validate\Valid]
        public PhoneNumber $phoneNumber,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_TOKEN)]
        #[OmitWhenEmpty]
        public ?string $twoFactorToken = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE)]
        #[OmitWhenEmpty]
        public ?string $twoFactorCode = null,
    ) {}
}
