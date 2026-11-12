<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One entry in the account's address book, as stored.
 *
 * To write an entry, use {@see \Polkurier\Webservice\Request\Address\SaveMessage}. `shared` is
 * read-only. The address book sends no street name, machine name or point, so those getters return
 * null.
 */
readonly class Address extends BaseAddress implements ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DEFAULT = 'default';
    public const string FIELD_SHARED = 'shared';
    public const string FIELD_COVER_ADDRESS = 'is_cover_address';
    /**-@#*/

    public const AddressTypeEnum DEFAULT_TYPE = AddressTypeEnum::Sender;

    /**
     * @param string $person
     * @param string $street
     * @param string $city
     * @param string $postcode
     * @param string $email
     * @param string $phone
     * @param string $name
     * @param string $houseNumber
     * @param string|null $company
     * @param string|null $province
     * @param string $country
     * @param string|null $pointId sent for recipients only
     * @param string|null $id
     * @param AddressTypeEnum|null $type
     * @param string|null $flatNumber
     * @param bool $default for a sender: the preselected sender, or the preselected alternative
     *        return address
     * @param bool $shared the entry belongs to the main account's address book, which this
     *        sub-account shares
     * @param bool $coverAddress the sender is an alternative return address; sent for senders only
     * @param string|null $streetName
     * @param string|null $machineName
     * @param CourierPoint|null $point
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        string $person,

        string $street,

        string $city,

        string $postcode,

        string $email,

        string $phone,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        string $houseNumber,

        ?string $company = null,

        ?string $province = null,

        string $country = self::DEFAULT_COUNTRY,

        ?string $pointId = null,

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
        private ?AddressTypeEnum $type = self::DEFAULT_TYPE,

        ?string $flatNumber = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_SHARED)]
        private bool $shared = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COVER_ADDRESS)]
        private bool $coverAddress = false,

        ?string $streetName = null,

        ?string $machineName = null,

        ?CourierPoint $point = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {
        parent::__construct(
            person: $person,
            street: $street,
            city: $city,
            postcode: $postcode,
            email: $email,
            phone: $phone,
            company: $company,
            province: $province,
            country: $country,
            pointId: $pointId,
            streetName: $streetName,
            machineName: $machineName,
            point: $point,
            houseNumber: $houseNumber,
            flatNumber: $flatNumber,
        );
    }

    /**
     * @return string|null null if the response leaves it out
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
     * @return AddressTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?AddressTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHouseNumber(): string
    {
        return parent::getHouseNumber() ?? '';
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
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @return bool
     */
    public function isCoverAddress(): bool
    {
        return $this->coverAddress;
    }
}
