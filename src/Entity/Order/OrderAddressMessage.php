<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Entity\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A sender or recipient address as `create_order` and `order_valuation_v2` accept it.
 *
 * This is a different shape from {@see \Polkurier\Webservice\Entity\Address\Address}, which is an
 * address book entry. That class has an id, a label and sharing flags that do not apply to orders,
 * and it has no `machinename`. Most fields overlap, but the two are not interchangeable.
 *
 * For a parcel machine or pickup point, set `pointId`. `machineName` is read only when `pointId` is
 * empty.
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country')]
readonly class OrderAddressMessage implements MessageInterface
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
    /**-@#*/

    public const string DEFAULT_COUNTRY = 'PL';

    /**
     * @param string $person contact name
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string $email
     * @param string $phone
     * @param string|null $company
     * @param string|null $houseNumber
     * @param string|null $flatNumber joined to the house number as `N/M`; set both
     * @param string $country ISO country code
     * @param string|null $pointId parcel machine or pickup point identifier
     * @param string|null $machineName the same identifier under another key, read only when
     *        $pointId is empty
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
        #[Validate\Email]
        private string $email,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PHONE)]
        #[Validate\PhoneNumber]
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
        #[Serializer\SerializedName(self::FIELD_HOUSE_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $houseNumber = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_FLAT_NUMBER)]
        #[OmitWhenEmpty]
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
        #[OmitWhenEmpty]
        private ?string $machineName = null,
    ) {}

    /**
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
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
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return string|null
     */
    public function getHouseNumber(): ?string
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
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * @return string|null
     */
    public function getMachineName(): ?string
    {
        return $this->machineName;
    }
}
