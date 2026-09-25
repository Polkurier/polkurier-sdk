<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\MoneyBox;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;

/**
 * Assembles a request to pay out collected cash-on-delivery money.
 *
 * All fields are optional. The destination defaults to the one in `get_money_box_config`. Set it
 * only to use a different one. The PIN and two-factor fields depend on what the account requires.
 */
class CreateMoneyBoxPayoutMessageBuilder extends AbstractBuilder
{
    /**
     * @param string[] $orders
     *
     * @return static
     */
    public function orders(array $orders): static
    {
        return $this->set('orders', $orders);
    }

    /**
     * @param ?MoneyBoxPayoutTypeEnum $payoutType
     *
     * @return static
     */
    public function payoutType(?MoneyBoxPayoutTypeEnum $payoutType): static
    {
        return $this->set('payoutType', $payoutType);
    }

    /**
     * @param ?MoneyBoxFeePaymentTypeEnum $feePaymentType
     *
     * @return static
     */
    public function feePaymentType(?MoneyBoxFeePaymentTypeEnum $feePaymentType): static
    {
        return $this->set('feePaymentType', $feePaymentType);
    }

    /**
     * @param ?string $bankAccountNumber
     *
     * @return static
     */
    public function bankAccountNumber(?string $bankAccountNumber): static
    {
        return $this->set('bankAccountNumber', $bankAccountNumber);
    }

    /**
     * @param ?string $company
     *
     * @return static
     */
    public function company(?string $company): static
    {
        return $this->set('company', $company);
    }

    /**
     * @param ?string $person
     *
     * @return static
     */
    public function person(?string $person): static
    {
        return $this->set('person', $person);
    }

    /**
     * @param ?string $street
     *
     * @return static
     */
    public function street(?string $street): static
    {
        return $this->set('street', $street);
    }

    /**
     * @param ?string $postcode
     *
     * @return static
     */
    public function postcode(?string $postcode): static
    {
        return $this->set('postcode', $postcode);
    }

    /**
     * @param ?string $city
     *
     * @return static
     */
    public function city(?string $city): static
    {
        return $this->set('city', $city);
    }

    /**
     * @param ?string $securityPin
     *
     * @return static
     */
    public function securityPin(?string $securityPin): static
    {
        return $this->set('securityPin', $securityPin);
    }

    /**
     * @param ?string $twoFactorToken
     *
     * @return static
     */
    public function twoFactorToken(?string $twoFactorToken): static
    {
        return $this->set('twoFactorToken', $twoFactorToken);
    }

    /**
     * @param ?string $twoFactorFormId
     *
     * @return static
     */
    public function twoFactorFormId(?string $twoFactorFormId): static
    {
        return $this->set('twoFactorFormId', $twoFactorFormId);
    }

    /**
     * @param ?string $twoFactorCode
     *
     * @return static
     */
    public function twoFactorCode(?string $twoFactorCode): static
    {
        return $this->set('twoFactorCode', $twoFactorCode);
    }

    /**
     * @return CreateMoneyBoxPayoutMessage
     * @throws InvalidArgumentException
     */
    public function build(): CreateMoneyBoxPayoutMessage
    {
        return $this->make(CreateMoneyBoxPayoutMessage::class);
    }
}
