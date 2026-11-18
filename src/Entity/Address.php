<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An address book entry, as you save it and as the API reports it.
 *
 * Pass an id to update that entry, or leave it out to create a new one. The name is the label the
 * entry is listed under and is always required. Each entry has one role, so a place used as both
 * sender and recipient is 2 entries with 2 ids.
 *
 * Put the house and flat numbers in their own fields, not at the end of the street. The API
 * stores the three as one line and splits them again on the way back, cutting at the first digit.
 * A street whose name contains a digit comes back cut at that digit, and a third part such as
 * `10/2/3` loses its tail, so an entry read back is not always the entry that was saved: compare
 * {@see self::getStreet()} against what you sent before relying on the parts.
 *
 * The street, house number and flat number together must be at least 2 words. A sender is always
 * checked; a recipient is checked unless its point is an InPost, Orlen or DPD pickup point. The
 * failure is reported under `housenumber`.
 *
 * `default` is for senders only: it makes this the preselected sender, or the preselected
 * alternative return address.
 *
 * A sender entry needs an email address, a recipient entry does not.
 *
 * `shared` and `point` are reported by the API and not sent.
 *
 * @api
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country', whenEmpty: self::DEFAULT_COUNTRY)]
#[Validate\RequiredWhen(
    field: 'email',
    property: 'type',
    equals: AddressTypeEnum::Sender,
    reason: 'is required for a sender entry',
)]
class Address implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
    public const string FIELD_SHARED = 'shared';
    public const string FIELD_POINT = 'point';
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
     * @param string|null $email
     * @param string $phone
     * @param string|null $id the entry to update, or null to create a new one
     * @param AddressTypeEnum|null $type the role the entry is filed under
     * @param string|null $company
     * @param string|null $flatNumber
     * @param string|null $province
     * @param string $country ISO 3166-1 alpha-2
     * @param string|null $pointId a pickup point this entry stands for; stored for recipients only
     * @param bool $default make this the preselected sender; send it only for a sender
     * @param bool $coverAddress the sender is an alternative return address
     * @param bool $shared the entry belongs to the main account's address book, which this
     *        sub-account shares
     * @param CourierPoint|null $point
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_NAME)]
        #[Validate\NotBlank]
        private string $name,

        #[Serializer\SerializedName(self::FIELD_PERSON)]
        #[Validate\NotBlank]
        #[PersonalData]
        private string $person,

        #[Serializer\SerializedName(self::FIELD_STREET)]
        #[Validate\NotBlank]
        #[PersonalData]
        private string $street,

        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[Validate\NotBlank]
        #[PersonalData]
        private string $houseNumber,

        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[Validate\NotBlank]
        #[PersonalData]
        private string $postcode,

        #[Serializer\SerializedName(self::FIELD_CITY)]
        #[Validate\NotBlank]
        #[PersonalData]
        private string $city,

        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        #[OmitWhenEmpty]
        #[Validate\Email]
        #[PersonalData]
        private ?string $email,

        #[Serializer\SerializedName(self::FIELD_PHONE)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        #[PersonalData]
        private string $phone,

        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[Validate\NotBlank]
        private ?AddressTypeEnum $type = self::DEFAULT_TYPE,

        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private ?string $company = null,

        #[Serializer\SerializedName(self::FIELD_FLAT_NUMBER)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private ?string $flatNumber = null,

        #[Serializer\SerializedName(self::FIELD_PROVINCE)]
        #[OmitWhenEmpty]
        private ?string $province = null,

        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        private string $country = self::DEFAULT_COUNTRY,

        #[Serializer\SerializedName(self::FIELD_POINT_ID)]
        #[OmitWhenEmpty]
        private ?string $pointId = null,

        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        private bool $coverAddress = false,

        #[Serializer\SerializedName(self::FIELD_SHARED)]
        #[ResponseOnly]
        private bool $shared = false,

        #[Serializer\SerializedName(self::FIELD_POINT)]
        #[ResponseOnly]
        private ?CourierPoint $point = null,

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return static
     */
    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return AddressTypeEnum|null
     */
    public function getType(): ?AddressTypeEnum
    {
        return $this->type;
    }

    /**
     * @param AddressTypeEnum|null $type
     *
     * @return static
     */
    public function setType(?AddressTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
    }

    /**
     * @param string $person
     *
     * @return static
     */
    public function setPerson(string $person): static
    {
        $this->person = $person;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @param string|null $company
     *
     * @return static
     */
    public function setCompany(?string $company): static
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     *
     * @return static
     */
    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     *
     * @return static
     */
    public function setHouseNumber(string $houseNumber): static
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFlatNumber(): ?string
    {
        return $this->flatNumber;
    }

    /**
     * @param string|null $flatNumber
     *
     * @return static
     */
    public function setFlatNumber(?string $flatNumber): static
    {
        $this->flatNumber = $flatNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     *
     * @return static
     */
    public function setPostcode(string $postcode): static
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return static
     */
    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProvince(): ?string
    {
        return $this->province;
    }

    /**
     * @param string|null $province
     *
     * @return static
     */
    public function setProvince(?string $province): static
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return static
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return static
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return static
     */
    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * @param string|null $pointId
     *
     * @return static
     */
    public function setPointId(?string $pointId): static
    {
        $this->pointId = $pointId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     *
     * @return static
     */
    public function setDefault(bool $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCoverAddress(): bool
    {
        return $this->coverAddress;
    }

    /**
     * @param bool $coverAddress
     *
     * @return static
     */
    public function setCoverAddress(bool $coverAddress): static
    {
        $this->coverAddress = $coverAddress;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * The pickup point this address resolves to, in full.
     *
     * Set when the address names a point that is still known. Null for an ordinary street address,
     * and also when the point could not be resolved. A point id with no point here may mean the point
     * has been withdrawn.
     *
     * @return CourierPoint|null
     */
    public function getPoint(): ?CourierPoint
    {
        return $this->point;
    }
}
