<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Payment;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;

/**
 * Assembles an online payment.
 *
 * Only the mode is required. The amount goes with a prepaid top-up, the invoice numbers with an
 * invoice payment and the order numbers with an order payment.
 */
class RegisterPaymentMessageBuilder extends AbstractBuilder
{
    /**
     * @param PaymentModeEnum $mode required before build()
     *
     * @return static
     */
    public function mode(PaymentModeEnum $mode): static
    {
        return $this->set('mode', $mode);
    }

    /**
     * @param ?float $amount
     *
     * @return static
     */
    public function amount(?float $amount): static
    {
        return $this->set('amount', $amount);
    }

    /**
     * @param string[] $invoiceNumbers
     *
     * @return static
     */
    public function invoiceNumbers(array $invoiceNumbers): static
    {
        return $this->set('invoiceNumbers', $invoiceNumbers);
    }

    /**
     * @param string[] $orderNumbers
     *
     * @return static
     */
    public function orderNumbers(array $orderNumbers): static
    {
        return $this->set('orderNumbers', $orderNumbers);
    }

    /**
     * @return RegisterPaymentMessage
     * @throws InvalidArgumentException
     */
    public function build(): RegisterPaymentMessage
    {
        return $this->make(RegisterPaymentMessage::class);
    }
}
