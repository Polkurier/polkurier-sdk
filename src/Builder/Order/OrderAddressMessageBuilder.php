<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;

/**
 * Assembles a sender or recipient address on an order.
 *
 * Which fields are required depends on the role the address plays and on whether the parcel is
 * going to a pickup point, so it is filled in as those become known.
 */
class OrderAddressMessageBuilder extends AbstractBuilder
{
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
     * @param string $city required before build()
     *
     * @return static
     */
    public function city(string $city): static
    {
        return $this->set('city', $city);
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
     * @param string|null $company
     *
     * @return static
     */
    public function company(?string $company): static
    {
        return $this->set('company', $company);
    }

    /**
     * @param string|null $houseNumber
     *
     * @return static
     */
    public function houseNumber(?string $houseNumber): static
    {
        return $this->set('houseNumber', $houseNumber);
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
     * @param string|null $machineName
     *
     * @return static
     */
    public function machineName(?string $machineName): static
    {
        return $this->set('machineName', $machineName);
    }

    /**
     * @return OrderAddressMessage
     * @throws InvalidArgumentException
     */
    public function build(): OrderAddressMessage
    {
        return $this->make(OrderAddressMessage::class);
    }
}
