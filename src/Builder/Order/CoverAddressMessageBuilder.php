<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\CoverAddressMessage;

/**
 * Assembles the alternative sender address printed on a label.
 */
class CoverAddressMessageBuilder extends AbstractBuilder
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
     * @param string|null $email
     *
     * @return static
     */
    public function email(?string $email): static
    {
        return $this->set('email', $email);
    }

    /**
     * @param string|null $phone
     *
     * @return static
     */
    public function phone(?string $phone): static
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
     * @param string|null $flatNumber joined to the house number as `N/M`; set both
     *
     * @return static
     */
    public function flatNumber(?string $flatNumber): static
    {
        return $this->set('flatNumber', $flatNumber);
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
     * @return CoverAddressMessage
     * @throws InvalidArgumentException
     */
    public function build(): CoverAddressMessage
    {
        return $this->make(CoverAddressMessage::class);
    }
}
