<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\PhoneNumber;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\PhoneNumber\SavePhoneNumberMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a telephone number being added to the account.
 *
 * The two-factor fields are only needed when the number is being made the default.
 */
class SavePhoneNumberMessageBuilder extends AbstractBuilder
{
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
     * @param string $name
     *
     * @return static
     */
    public function name(string $name): static
    {
        return $this->set('name', $name);
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
     * @return SavePhoneNumberMessage
     * @throws InvalidArgumentException
     */
    public function build(): SavePhoneNumberMessage
    {
        return $this->make(SavePhoneNumberMessage::class);
    }
}
