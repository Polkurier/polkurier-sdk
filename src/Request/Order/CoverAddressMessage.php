<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The alternative sender address printed on the label, when the order uses the
 * {@see \Polkurier\Webservice\Enum\Order\AdditionalServiceEnum::CoverSenderAddress} service.
 *
 * Note the key spelling: this uses `flat_number` with an underscore, while sender and recipient
 * addresses use `flatnumber`. The API defines them that way.
 */
readonly class CoverAddressMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PERSON = 'person';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_STREET = 'street';
    public const string FIELD_FLAT_NUMBER = 'flat_number';
    public const string FIELD_CITY = 'city';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_PHONE = 'phone';
    /**-@#*/

    /**
     * @param string $person
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string $email
     * @param string $phone
     * @param string|null $company
     * @param string|null $flatNumber
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
        #[Validate\Postcode]
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
    public function getFlatNumber(): ?string
    {
        return $this->flatNumber;
    }
}
