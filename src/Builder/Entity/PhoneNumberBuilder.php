<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\PhoneNumber;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a phone number being added to the account.
 *
 * @api
 */
class PhoneNumberBuilder extends AbstractBuilder
{
    /**
     * @param string $number required when sending
     *
     * @return static
     */
    public function setNumber(string $number): static
    {
        return $this->set('number', $number);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param bool $default
     *
     * @return static
     */
    public function setDefault(bool $default): static
    {
        return $this->set('default', $default);
    }

    /**
     * @return PhoneNumber
     * @throws InvalidArgumentException
     */
    public function build(): PhoneNumber
    {
        return $this->make(PhoneNumber::class);
    }
}
