<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Enum\Account\PayerUserTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Writes one of the account's three addresses: billing, correspondence or contact.
 *
 * Two things gate this before any field is looked at. The whole call is refused once the account
 * has been issued an invoice — at that point the billing details are locked and only support can
 * change them. And the legal form is mandatory on the billing address specifically; on the other
 * two it is ignored.
 *
 * The correspondence and contact addresses can be declared identical to the billing one instead of
 * being filled in, which is what `same_as_payer` is for.
 *
 * The tax number is split into a country prefix and the number itself, so a Polish NIP goes in as
 * prefix "PL" and the digits separately.
 */
#[Validate\RequiredWhen(
    field: 'userType',
    property: 'addressType',
    equals: PayerAddressTypeEnum::Payer,
    because: 'is required for the billing address',
)]
#[Validate\PostcodeForCountry(postcode: 'postCode', country: 'country')]
readonly class SavePayerAddressMessage implements MessageInterface
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
     * @param PayerAddressTypeEnum $addressType which of the three addresses to write
     * @param PayerUserTypeEnum|null $userType required for the billing address, ignored otherwise
     * @param bool $sameAsPayer declare this address identical to the billing one
     * @param string $name company or personal name
     * @param string $street
     * @param string $buildingNumber
     * @param string $postCode
     * @param string $city
     * @param string $country ISO country code
     * @param string $nipPrefix country prefix of the tax number, e.g. "PL"
     * @param string $nip tax identification number, digits only
     * @param string $regon Polish statistical number
     */
    public function __construct(
        /**
         * @var PayerAddressTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_ADDRESS_TYPE)]
        private PayerAddressTypeEnum $addressType,

        /**
         * @var ?PayerUserTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_USER_TYPE)]
        #[OmitWhenEmpty]
        private ?PayerUserTypeEnum $userType = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SAME_AS_PAYER)]
        private bool $sameAsPayer = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        private string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_BUILDING_NUMBER)]
        private string $buildingNumber = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POST_CODE)]
        private string $postCode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        private string $city = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        private string $country = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NIP_PREFIX)]
        private string $nipPrefix = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NIP)]
        private string $nip = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REGON)]
        private string $regon = '',
    ) {}

    /**
     * @return PayerAddressTypeEnum
     */
    public function getAddressType(): PayerAddressTypeEnum
    {
        return $this->addressType;
    }

    /**
     * @return PayerUserTypeEnum|null
     */
    public function getUserType(): ?PayerUserTypeEnum
    {
        return $this->userType;
    }

    /**
     * @return bool
     */
    public function isSameAsPayer(): bool
    {
        return $this->sameAsPayer;
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
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getBuildingNumber(): string
    {
        return $this->buildingNumber;
    }

    /**
     * @return string
     */
    public function getPostCode(): string
    {
        return $this->postCode;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getNipPrefix(): string
    {
        return $this->nipPrefix;
    }

    /**
     * @return string
     */
    public function getNip(): string
    {
        return $this->nip;
    }

    /**
     * @return string
     */
    public function getRegon(): string
    {
        return $this->regon;
    }
}
