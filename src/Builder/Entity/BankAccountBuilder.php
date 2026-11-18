<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Entity;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a bank account to register or update.
 *
 * @api
 */
class BankAccountBuilder extends AbstractBuilder
{
    /**
     * @param string $name required before build()
     *
     * @return static
     */
    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    /**
     * @param string $number required before build()
     *
     * @return static
     */
    public function setNumber(string $number): static
    {
        return $this->set('number', $number);
    }

    /**
     * @param string|null $id the account to update, or null to register a new one
     *
     * @return static
     */
    public function setId(?string $id): static
    {
        return $this->set('id', $id);
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
     * @return BankAccount
     * @throws InvalidArgumentException when a field the message requires was never given
     */
    public function build(): BankAccount
    {
        return $this->make(BankAccount::class);
    }
}
