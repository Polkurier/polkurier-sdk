<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\BankAccount;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\BankAccount\SaveMessage;

/**
 * Assembles a bank account for cash-on-delivery settlement.
 *
 * Whether the security PIN and the two-factor fields are needed depends on the account, which is
 * known from get_account_info rather than at the call site — so they are set conditionally.
 */
class SaveMessageBuilder extends AbstractBuilder
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
     * @return SaveMessage
     * @throws InvalidArgumentException
     */
    public function build(): SaveMessage
    {
        return $this->make(SaveMessage::class);
    }
}
