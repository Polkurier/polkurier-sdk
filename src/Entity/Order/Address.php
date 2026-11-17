<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A sender or recipient address as `create_order` and `order_valuation_v2` accept it.
 *
 * This is a different shape from {@see \Polkurier\Webservice\Entity\Address}, which is an
 * address book entry. That class has an id, a label, a role, a default flag and sharing flags that
 * do not apply to orders. The two also differ in what the API reads: an order address is sent with
 * `machinename` and never with `province`, a book entry the other way round. Most fields overlap,
 * but the two are not interchangeable.
 *
 * For a parcel machine or pickup point, set `pointId`. The API answers with the same code under
 * `machinename` as well, which {@see self::getMachineName()} reports and nothing sends.
 *
 * An order's cover address is a sender address the carrier prints, not a shape of its own, so it
 * is this record again. The API reads that block under its own keys: the flat number goes under
 * {@see self::FIELD_COVER_FLAT_NUMBER} and the fields it doesn't read are dropped, see
 * {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer}.
 *
 * @api
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country', whenEmpty: self::DEFAULT_COUNTRY)]
class Address implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PERSON = 'person';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_STREET = 'street';
    public const string FIELD_HOUSE_NUMBER = 'housenumber';
    public const string FIELD_FLAT_NUMBER = 'flatnumber';
    public const string FIELD_CITY = 'city';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_COUNTRY = 'country';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_PHONE = 'phone';
    public const string FIELD_POINT_ID = 'point_id';
    public const string FIELD_MACHINE_NAME = 'machinename';
    public const string FIELD_PROVINCE = 'province';
    public const string FIELD_STREET_NAME = 'streetname';
    public const string FIELD_POINT = 'point';
    /**
     * The flat number's key inside an order's `cover_address`, spelled with an underscore unlike
     * everywhere else. {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer} writes it.
     */
    public const string FIELD_COVER_FLAT_NUMBER = 'flat_number';
    /**-@#*/

    public const string DEFAULT_COUNTRY = 'PL';

    /**
     * @param string $person contact name
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $company
     * @param string|null $houseNumber
     * @param string|null $flatNumber joined to the house number as `N/M`; set both
     * @param string $country ISO country code
     * @param string|null $pointId parcel machine or pickup point identifier
     * @param string|null $machineName the point identifier as the API reports it back, a copy of
     *        $pointId
     * @param string|null $province
     * @param string|null $streetName the street without the house and flat number, split from
     *        $street. Empty when the street can't be split
     * @param CourierPoint|null $point
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        #[PersonalData]
        private string $person,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        #[PersonalData]
        private string $street,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        #[PersonalData]
        private string $city,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[PersonalData]
        private string $postcode,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        #[OmitWhenEmpty]
        #[Validate\Email]
        #[PersonalData]
        private ?string $email = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        #[OmitWhenEmpty]
        #[Validate\PhoneNumber]
        #[PersonalData]
        private ?string $phone = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private ?string $company = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private ?string $houseNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_FLAT_NUMBER)]
        #[OmitWhenEmpty]
        #[PersonalData]
        private ?string $flatNumber = null,

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
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_MACHINE_NAME)]
        #[ResponseOnly]
        private ?string $machineName = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PROVINCE)]
        #[ResponseOnly]
        private ?string $province = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_STREET_NAME)]
        #[ResponseOnly]
        #[PersonalData]
        private ?string $streetName = null,

        /**
         * @var CourierPoint|null
         */
        #[Serializer\SerializedName(self::FIELD_POINT)]
        #[ResponseOnly]
        private ?CourierPoint $point = null,
    ) {}

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
     * The point identifier as the API reports it back, the same value as
     * {@see self::getPointId()}.
     *
     * @return string|null
     */
    public function getMachineName(): ?string
    {
        return $this->machineName;
    }

    /**
     * @return string|null null if the response leaves it out
     */
    public function getProvince(): ?string
    {
        return $this->province;
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
