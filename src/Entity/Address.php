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
 * Put the house and flat numbers in their own fields, not at the end of the street. The API joins
 * them into one line and requires at least two words, which it reports under `housenumber`.
 *
 * `default` is for senders only: it makes this the preselected sender, or the preselected
 * alternative return address.
 *
 * A sender entry needs an email address, a recipient entry does not.
 *
 * `shared`, `streetName` and `point` are reported by the API and not sent.
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
    public const string FIELD_STREET_NAME = 'streetname';
    public const string FIELD_POINT = 'point';
    /**-@#*/

    public const string DEFAULT_COUNTRY = 'PL';
    public const AddressTypeEnum DEFAULT_TYPE = AddressTypeEnum::Sender;

    /**
     * @param string $name the label the entry is listed under
     * @param string $person contact person at the address
     * @param string $street street, without the house number
     * @param string|null $houseNumber
     * @param string $postcode
     * @param string $city
     * @param string|null $email
     * @param string|null $phone
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
     * @param string|null $streetName the street without the house and flat number, split from
     * @param CourierPoint|null $point
     * @param string|null $rawResponse the body of the response, as the API sent it
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
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[Validate\NotBlank]
        private ?string $houseNumber,

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
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        #[OmitWhenEmpty]
        #[Validate\Email]
        private ?string $email,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        private ?string $phone,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var AddressTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[Validate\NotBlank]
        private ?AddressTypeEnum $type = self::DEFAULT_TYPE,

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

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SHARED)]
        #[ResponseOnly]
        private bool $shared = false,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_STREET_NAME)]
        #[ResponseOnly]
        private ?string $streetName = null,

        /**
         * @var CourierPoint|null
         */
        #[Serializer\SerializedName(self::FIELD_POINT)]
        #[ResponseOnly]
        private ?CourierPoint $point = null,

        /**
         * @var string|null
         */
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
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * @param string|null $houseNumber
     *
     * @return static
     */
    public function setHouseNumber(?string $houseNumber): static
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
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     *
     * @return static
     */
    public function setPhone(?string $phone): static
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
     * @param bool $shared
     *
     * @return static
     */
    public function setShared(bool $shared): static
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * The street name without the house and flat number, split from {@see self::getStreet()}.
     *
     * The split doesn't always succeed. When it doesn't, this, the house number and the flat number
     * are empty while the street still holds the full text, so rely on the street.
     *
     * @return string|null null if the response leaves it out
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * @param string|null $streetName
     *
     * @return static
     */
    public function setStreetName(?string $streetName): static
    {
        $this->streetName = $streetName;

        return $this;
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

    /**
     * @param CourierPoint|null $point
     *
     * @return static
     */
    public function setPoint(?CourierPoint $point): static
    {
        $this->point = $point;

        return $this;
    }
}
