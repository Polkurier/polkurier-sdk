<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a sender or recipient address on an order.
 *
 * build() needs every setter marked required, but accepts an empty string. Which fields must be
 * filled in depends on whether this is the sender or the recipient, and on whether the parcel is
 * going to a pickup point.
 *
 * @api
 */
class AddressBuilder extends AbstractBuilder
{
    /**
     * @param string $person required before build()
     *
     * @return static
     */
    public function setPerson(string $person): static
    {
        return $this->set('person', $person);
    }

    /**
     * @param string $street required before build()
     *
     * @return static
     */
    public function setStreet(string $street): static
    {
        return $this->set('street', $street);
    }

    /**
     * @param string $city required before build()
     *
     * @return static
     */
    public function setCity(string $city): static
    {
        return $this->set('city', $city);
    }

    /**
     * @param string $postcode required before build()
     *
     * @return static
     */
    public function setPostcode(string $postcode): static
    {
        return $this->set('postcode', $postcode);
    }

    /**
     * @param string $email optional
     *
     * @return static
     */
    public function setEmail(string $email): static
    {
        return $this->set('email', $email);
    }

    /**
     * @param string $phone optional
     *
     * @return static
     */
    public function setPhone(string $phone): static
    {
        return $this->set('phone', $phone);
    }

    /**
     * @param string|null $company
     *
     * @return static
     */
    public function setCompany(?string $company): static
    {
        return $this->set('company', $company);
    }

    /**
     * @param string|null $houseNumber
     *
     * @return static
     */
    public function setHouseNumber(?string $houseNumber): static
    {
        return $this->set('houseNumber', $houseNumber);
    }

    /**
     * @param string|null $flatNumber joined to the house number as `N/M`; set both
     *
     * @return static
     */
    public function setFlatNumber(?string $flatNumber): static
    {
        return $this->set('flatNumber', $flatNumber);
    }

    /**
     * @param string $country
     *
     * @return static
     */
    public function setCountry(string $country): static
    {
        return $this->set('country', $country);
    }

    /**
     * @param string|null $pointId
     *
     * @return static
     */
    public function setPointId(?string $pointId): static
    {
        return $this->set('pointId', $pointId);
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
