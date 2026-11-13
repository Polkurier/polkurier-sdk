<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Enum\Account\PayerUserTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Saves one of the account's 3 addresses: billing, correspondence or contact.
 *
 * Once the account has been issued an invoice, the call is refused. The billing details are then
 * locked and only support can change them. The legal form (`user_type`) is required on the billing
 * address only. Leave it out on the other two.
 *
 * Set `same_as_payer` to make the correspondence or contact address the same as the billing one
 * instead of filling it in.
 *
 * The tax number is split into a country prefix and the number. For a Polish NIP, send "PL" as the
 * prefix and the digits separately.
 */
#[Validate\RequiredWhen(
    field: 'userType',
    property: 'addressType',
    equals: PayerAddressTypeEnum::Payer,
    because: 'is required for the billing address',
)]
#[Validate\PostcodeForCountry(postcode: 'postCode', country: 'country')]
final readonly class SavePayerAddressMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ADDRESS_TYPE = 'address_type';
    public const string FIELD_USER_TYPE = 'user_type';
    public const string FIELD_SAME_AS_PAYER = 'same_as_payer';
    public const string FIELD_NAME = 'name';
    public const string FIELD_STREET = 'street';
    public const string FIELD_BUILDING_NUMBER = 'building_number';
    public const string FIELD_POST_CODE = 'post_code';
    public const string FIELD_CITY = 'city';
    public const string FIELD_COUNTRY = 'country';
    public const string FIELD_NIP_PREFIX = 'nip_prefix';
    public const string FIELD_NIP = 'nip';
    public const string FIELD_REGON = 'regon';
    /**-@#*/

    /**
     * @param PayerAddressTypeEnum $addressType which of the 3 addresses to save
     * @param PayerUserTypeEnum|null $userType required for the billing address only
     * @param bool $sameAsPayer make this address the same as the billing one
     * @param string $name company or personal name
     * @param string $street
     * @param string $buildingNumber
     * @param string $postCode
     * @param string $city
     * @param string $country ISO country code
     * @param string $nipPrefix country prefix of the tax number, e.g. "PL". Send it explicitly: with
     *        "PL", a company's NIP and REGON are checked
     * @param string $nip tax identification number, digits only
     * @param string $regon Polish statistical number. Required for a company whose tax number
     *        prefix is "PL": 9 or 14 digits with a valid check digit
     */
    public function __construct(
        /**
         * @var PayerAddressTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_ADDRESS_TYPE)]
        public PayerAddressTypeEnum $addressType,

        /**
         * @var ?PayerUserTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_USER_TYPE)]
        #[OmitWhenEmpty]
        public ?PayerUserTypeEnum $userType = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SAME_AS_PAYER)]
        public bool $sameAsPayer = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        #[OmitWhenEmpty]
        public string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        #[OmitWhenEmpty]
        public string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_BUILDING_NUMBER)]
        #[OmitWhenEmpty]
        public string $buildingNumber = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POST_CODE)]
        #[OmitWhenEmpty]
        public string $postCode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        #[OmitWhenEmpty]
        public string $city = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        #[OmitWhenEmpty]
        public string $country = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NIP_PREFIX)]
        #[OmitWhenEmpty]
        public string $nipPrefix = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NIP)]
        #[OmitWhenEmpty]
        public string $nip = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REGON)]
        #[OmitWhenEmpty]
        public string $regon = '',
    ) {}
}
