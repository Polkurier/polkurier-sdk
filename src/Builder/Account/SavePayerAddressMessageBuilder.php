<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Account;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;
use Polkurier\Webservice\Enum\Account\PayerUserTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Account\SavePayerAddressMessage;

/**
 * Assembles one of the account's addresses: billing, correspondence or contact.
 *
 * Only the address type is required. Which of the other fields apply depends on the address type
 * and on whether the payer is a company.
 */
class SavePayerAddressMessageBuilder extends AbstractBuilder
{
    /**
     * @param PayerAddressTypeEnum $addressType required before build()
     *
     * @return static
     */
    public function addressType(PayerAddressTypeEnum $addressType): static
    {
        return $this->set('addressType', $addressType);
    }

    /**
     * @param PayerUserTypeEnum|null $userType
     *
     * @return static
     */
    public function userType(?PayerUserTypeEnum $userType): static
    {
        return $this->set('userType', $userType);
    }

    /**
     * @param bool $sameAsPayer
     *
     * @return static
     */
    public function sameAsPayer(bool $sameAsPayer): static
    {
        return $this->set('sameAsPayer', $sameAsPayer);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param string $street
     *
     * @return static
     */
    public function street(string $street): static
    {
        return $this->set('street', $street);
    }

    /**
     * @param string $buildingNumber
     *
     * @return static
     */
    public function buildingNumber(string $buildingNumber): static
    {
        return $this->set('buildingNumber', $buildingNumber);
    }

    /**
     * @param string $postCode
     *
     * @return static
     */
    public function postCode(string $postCode): static
    {
        return $this->set('postCode', $postCode);
    }

    /**
     * @param string $city
     *
     * @return static
     */
    public function city(string $city): static
    {
        return $this->set('city', $city);
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
     * @param string $nipPrefix
     *
     * @return static
     */
    public function nipPrefix(string $nipPrefix): static
    {
        return $this->set('nipPrefix', $nipPrefix);
    }

    /**
     * @param string $nip
     *
     * @return static
     */
    public function nip(string $nip): static
    {
        return $this->set('nip', $nip);
    }

    /**
     * @param string $regon
     *
     * @return static
     */
    public function regon(string $regon): static
    {
        return $this->set('regon', $regon);
    }

    /**
     * @return SavePayerAddressMessage
     * @throws InvalidArgumentException
     */
    public function build(): SavePayerAddressMessage
    {
        return $this->make(SavePayerAddressMessage::class);
    }
}
