<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The address fields common to everything that carries an address.
 *
 * A pickup point is expressed by setting the point id; the street fields then serve as contact
 * details rather than a delivery destination.
 */
class BaseAddress implements MessageInterface
{
    public const string DEFAULT_COUNTRY = 'PL';

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_PERSON = 'person';
    public const string FIELD_STREET = 'street';
    public const string FIELD_CITY = 'city';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_PROVINCE = 'province';
    public const string FIELD_COUNTRY = 'country';
    public const string FIELD_POINT_ID = 'point_id';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_PHONE = 'phone';
    public const string FIELD_STREET_NAME = 'streetname';
    public const string FIELD_MACHINE_NAME = 'machinename';
    public const string FIELD_POINT = 'point';
    /**-@#*/

    /**
     * @param string $person
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string $email
     * @param string $phone
     * @param string|null $company
     * @param string|null $province
     * @param string $country
     * @param string|null $pointId
     * @param string|null $streetName
     * @param string|null $machineName
     * @param CourierPoint|null $point
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        private string $person,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        private string $street,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        private string $city,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        private string $postcode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        private string $email,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        private string $phone,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        #[OmitWhenEmpty]
        private ?string $company = null,

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
        #[OmitWhenEmpty]
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
        #[Serializer\SerializedName(self::FIELD_STREET_NAME)]
        #[OmitWhenEmpty]
        private ?string $streetName = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_MACHINE_NAME)]
        #[OmitWhenEmpty]
        private ?string $machineName = null,

        /**
         * @var CourierPoint|null
         */
        #[Serializer\SerializedName(self::FIELD_POINT)]
        #[OmitWhenEmpty]
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
     * @return void
     */
    public function setPerson(string $person): void
    {
        $this->person = $person;
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
     * @return void
     */
    public function setCompany(?string $company): void
    {
        $this->company = $company;
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
     * @return void
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
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
     * @return void
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
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
     * @return void
     */
    public function setPostcode(string $postcode): void
    {
        $this->postcode = $postcode;
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
     * @return void
     */
    public function setProvince(?string $province): void
    {
        $this->province = $province;
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
     * @return void
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
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
     * @return void
     */
    public function setPointId(?string $pointId): void
    {
        $this->pointId = $pointId;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
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
     * @return void
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * The street without its house number, split out by the API.
     *
     * The street with the house number taken off it, as the API parses it.
     *
     * @return string|null
     */
    public function getStreetName(): ?string
    {
        return $this->streetName;
    }

    /**
     * Name of the parcel machine, for carriers that identify one by name rather than by code.
     *
     * @return string|null
     */
    public function getMachineName(): ?string
    {
        return $this->machineName;
    }

    /**
     * The pickup point this address resolves to, expanded in full.
     *
     * Populated by the API when the address names a point and the point is still known. Null both
     * when the address is an ordinary street address and when the point could not be resolved, so
     * a point id without a point here means the point has been withdrawn.
     *
     * @return CourierPoint|null
     */
    public function getPoint(): ?CourierPoint
    {
        return $this->point;
    }
}
