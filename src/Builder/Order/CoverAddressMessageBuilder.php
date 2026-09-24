<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Order\CoverAddressMessage;

/**
 * Assembles the alternative return address printed on a label.
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
     * @param string|null $flatNumber
     *
     * @return static
     */
    public function flatNumber(?string $flatNumber): static
    {
        return $this->set('flatNumber', $flatNumber);
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
