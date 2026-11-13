<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles an address book entry.
 *
 * Set an id to update an existing entry. Without one, a new entry is created.
 *
 * @api
 */
class AddressBuilder extends AbstractBuilder
{
    /**
     * @param string $name required before build()
     *
     * @return static
     */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param string $person required before build()
     *
     * @return static
     */
    public function person(string $person): static
    {
        return $this->set('person', $person);
    }

    /**
     * @param string $street required before build()
     *
     * @return static
     */
    public function street(string $street): static
    {
        return $this->set('street', $street);
    }

    /**
     * @param string $houseNumber required before build()
     *
     * @return static
     */
    public function houseNumber(string $houseNumber): static
    {
        return $this->set('houseNumber', $houseNumber);
    }

    /**
     * @param string $postcode required before build()
     *
     * @return static
     */
    public function postcode(string $postcode): static
    {
        return $this->set('postcode', $postcode);
    }

    /**
     * @param string $city required before build()
     *
     * @return static
     */
    public function city(string $city): static
    {
        return $this->set('city', $city);
    }

    /**
     * @param string $email required before build()
     *
     * @return static
     */
    public function email(string $email): static
    {
        return $this->set('email', $email);
    }

    /**
     * @param string $phone required before build()
     *
     * @return static
     */
    public function phone(string $phone): static
    {
        return $this->set('phone', $phone);
    }

    /**
     * @param string|null $id
     *
     * @return static
     */
    public function id(?string $id): static
    {
        return $this->set('id', $id);
    }

    /**
     * @param AddressTypeEnum $type
     *
     * @return static
     */
    public function type(AddressTypeEnum $type): static
    {
        return $this->set('type', $type);
    }

    /**
     * @param string|null $company
     *
     * @return static
     */
    public function company(?string $company): static
    {
        return $this->set('company', $company);
    }

    /**
     * @param string|null $flatNumber
     *
     * @return static
     */
    public function flatNumber(?string $flatNumber): static
    {
        return $this->set('flatNumber', $flatNumber);
    }

    /**
     * @param string|null $province
     *
     * @return static
     */
    public function province(?string $province): static
    {
        return $this->set('province', $province);
    }

    /**
     * @param string $country
     *
     * @return static
     */
    public function country(string $country): static
    {
        return $this->set('country', $country);
    }

    /**
     * @param string|null $pointId
     *
     * @return static
     */
    public function pointId(?string $pointId): static
    {
        return $this->set('pointId', $pointId);
    }

    /**
     * @param bool $default
     *
     * @return static
     */
    public function default(bool $default): static
    {
        return $this->set('default', $default);
    }

    /**
     * @param bool $coverAddress
     *
     * @return static
     */
    public function coverAddress(bool $coverAddress): static
    {
        return $this->set('coverAddress', $coverAddress);
    }

    /**
     * @return Address
     * @throws InvalidArgumentException
     */
    public function build(): Address
    {
        return $this->make(Address::class);
    }
}
