<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\BankAccount;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a bank account to register or update.
 *
 * While `get_account_info` reports the bank-account PIN lock on, every save needs the security PIN.
 * Otherwise registering, or changing the number or default flag, needs the two-factor fields.
 */
class SaveBankAccountMessageBuilder extends AbstractBuilder
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
     * @param string $number required before build()
     *
     * @return static
     */
    public function number(string $number): static
    {
        return $this->set('number', $number);
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
     * @param bool $default
     *
     * @return static
     */
    public function default(bool $default): static
    {
        return $this->set('default', $default);
    }

    /**
     * @param string|null $twoFactorToken
     *
     * @return static
     */
    public function twoFactorToken(?string $twoFactorToken): static
    {
        return $this->set('twoFactorToken', $twoFactorToken);
    }

    /**
     * @param string|null $twoFactorCode
     *
     * @return static
     */
    public function twoFactorCode(?string $twoFactorCode): static
    {
        return $this->set('twoFactorCode', $twoFactorCode);
    }

    /**
     * @param string|null $accessPin
     *
     * @return static
     */
    public function accessPin(?string $accessPin): static
    {
        return $this->set('accessPin', $accessPin);
    }

    /**
     * @return SaveBankAccountMessage
     * @throws InvalidArgumentException
     */
    public function build(): SaveBankAccountMessage
    {
        return $this->make(SaveBankAccountMessage::class);
    }
}
