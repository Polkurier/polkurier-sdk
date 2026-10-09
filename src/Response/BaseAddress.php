<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Address fields shared by every message that carries an address.
 *
 * To address a pickup point, set the point id. The street fields then act as contact details, not
 * as the delivery destination.
 */
readonly class BaseAddress implements MessageInterface
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
    public const string FIELD_HOUSE_NUMBER = 'housenumber';
    public const string FIELD_FLAT_NUMBER = 'flatnumber';
    /**-@#*/

    /**
     * @param string $person
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string $email empty when the address has none, as on an order's cover address
     * @param string $phone
     * @param string|null $company
     * @param string|null $province
     * @param string $country
     * @param string|null $pointId
     * @param string|null $streetName the street without the house and flat number, split from
     *        $street. Empty when the street can't be split
     * @param string|null $machineName
     * @param CourierPoint|null $point
     * @param string|null $houseNumber split from $street, like $streetName
     * @param string|null $flatNumber split from $street, like $streetName
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
        private string $email = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        private string $phone = '',

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

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $houseNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_FLAT_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $flatNumber = null,
    ) {}

    /**
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
    }

    /**
     * @return string|null empty or null when the address has none
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
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @return string|null null if the response leaves it out
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
     * @return string|null empty or null when the address isn't a pickup point. Always empty on an
     *         order's cover address
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * @return string empty when the address has none, as on an order's cover address
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
     * The house number, split from {@see self::getStreet()}. See {@see self::getStreetName()}.
     *
     * @return string|null null if the response leaves it out
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * The flat number, split from {@see self::getStreet()}. See {@see self::getStreetName()}.
     *
     * @return string|null null if the response leaves it out, empty when the address has none
     */
    public function getFlatNumber(): ?string
    {
        return $this->flatNumber;
    }

    /**
     * Name of the parcel machine, for carriers that identify machines by name instead of by code.
     *
     * On an order's sender and recipient, it holds the same value as {@see self::getPointId()}.
     *
     * @return string|null null if the response leaves it out
     */
    public function getMachineName(): ?string
    {
        return $this->machineName;
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
