<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Order;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Entity\Order\CodMessage;
use Polkurier\Webservice\Enum\Order\CodReturnTypeEnum;
use Polkurier\Webservice\Enum\Order\CodTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles the cash-on-delivery block of an order.
 *
 * Set the bank account only when the money is paid out to one.
 */
class CodMessageBuilder extends AbstractBuilder
{
    /**
     * @param float $amount required before build()
     *
     * @return static
     */
    public function amount(float $amount): static
    {
        return $this->set('amount', $amount);
    }

    /**
     * @param string $bankAccount
     *
     * @return static
     */
    public function bankAccount(string $bankAccount): static
    {
        return $this->set('bankAccount', $bankAccount);
    }

    /**
     * @param CodTypeEnum $type
     *
     * @return static
     */
    public function type(CodTypeEnum $type): static
    {
        return $this->set('type', $type);
    }

    /**
     * @param CodReturnTypeEnum $returnType
     *
     * @return static
     */
    public function returnType(CodReturnTypeEnum $returnType): static
    {
        return $this->set('returnType', $returnType);
    }

    /**
     * @return CodMessage
     * @throws InvalidArgumentException
     */
    public function build(): CodMessage
    {
        return $this->make(CodMessage::class);
    }
}
