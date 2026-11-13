<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Address;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An address book entry to save.
 *
 * Pass an id to update that entry, or leave it out to create a new one. The name is the label the
 * entry is listed under and is always required. Each entry has one role, so a place used as both
 * sender and recipient is 2 entries with 2 ids.
 *
 * Put the house and flat numbers in their own fields, not at the end of the street. The API
 * validates the house number on its own.
 *
 * `default` is for senders only: it makes this the preselected sender, or the preselected
 * alternative return address.
 *
 * Whether the entry is shared can't be set here; {@see \Polkurier\Webservice\Entity\Address\Address}
 * reports it.
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country')]
readonly class SaveAddressMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_PERSON = 'person';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_STREET = 'street';
    public const string FIELD_HOUSE_NUMBER = 'housenumber';
    public const string FIELD_FLAT_NUMBER = 'flatnumber';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_CITY = 'city';
    public const string FIELD_PROVINCE = 'province';
    public const string FIELD_COUNTRY = 'country';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_PHONE = 'phone';
    public const string FIELD_POINT_ID = 'point_id';
    public const string FIELD_DEFAULT = 'default';
    public const string FIELD_COVER_ADDRESS = 'is_cover_address';
    /**-@#*/

    public const string DEFAULT_COUNTRY = 'PL';
    public const AddressTypeEnum DEFAULT_TYPE = AddressTypeEnum::Sender;

    /**
     * @param string $name the label the entry is listed under
     * @param string $person contact person at the address
     * @param string $street street, without the house number
     * @param string $houseNumber
     * @param string $postcode
     * @param string $city
     * @param string $email
     * @param string $phone
     * @param string|null $id the entry to update, or null to create a new one
     * @param AddressTypeEnum $type the role the entry is filed under
     * @param string|null $company
     * @param string|null $flatNumber
     * @param string|null $province
     * @param string $country ISO 3166-1 alpha-2
     * @param string|null $pointId a pickup point this entry stands for; stored for recipients only
     * @param bool $default make this the preselected sender; send it only for a sender
     * @param bool $coverAddress the sender is an alternative return address
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
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        #[Validate\NotBlank]
        private string $person,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        #[Validate\NotBlank]
        private string $street,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[Validate\NotBlank]
        private string $houseNumber,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[Validate\NotBlank]
        private string $postcode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        #[Validate\NotBlank]
        private string $city,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        #[Validate\Email]
        private string $email,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        private string $phone,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var AddressTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private AddressTypeEnum $type = self::DEFAULT_TYPE,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        #[OmitWhenEmpty]
        private ?string $company = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_FLAT_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $flatNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PROVINCE)]
        #[OmitWhenEmpty]
        private ?string $province = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        private string $country = self::DEFAULT_COUNTRY,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_POINT_ID)]
        #[OmitWhenEmpty]
        private ?string $pointId = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        private bool $coverAddress = false,
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
     * @return AddressTypeEnum
     */
    public function getType(): AddressTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
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
    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    /**
     * @return string|null
     */
    public function getFlatNumber(): ?string
    {
        return $this->flatNumber;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getProvince(): ?string
    {
        return $this->province;
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
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function isCoverAddress(): bool
    {
        return $this->coverAddress;
    }
}
