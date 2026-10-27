<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A payout request and its progress.
 *
 * The amounts show the total requested, the part still to be paid, the part already paid and
 * anything withheld as compensation against debts.
 *
 * Check the cancellable flag before offering a cancel action. It turns false once any part of the
 * payout is on its way or paid, and once its fee is paid other than on an invoice.
 */
readonly class MoneyBoxPayoutMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_DATE = 'date';
    public const string FIELD_REQUEST_TYPE = 'request_type';
    public const string FIELD_PAYOUT_TYPE = 'payout_type';
    public const string FIELD_BANK_ACCOUNT_NUMBER = 'bank_account_number';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_PERSON = 'person';
    public const string FIELD_STREET = 'street';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_CITY = 'city';
    public const string FIELD_FEE = 'fee';
    public const string FIELD_FEE_NETT = 'fee_nett';
    public const string FIELD_FEE_VAT_RATE = 'fee_vat_rate';
    public const string FIELD_FEE_PAYMENT_TYPE = 'fee_payment_type';
    public const string FIELD_FEE_PAYMENT_STATUS = 'fee_payment_status';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_TO_PAY_AMOUNT = 'to_pay_amount';
    public const string FIELD_PAID_AMOUNT = 'paid_amount';
    public const string FIELD_COMPENSATION_AMOUNT = 'compensation_amount';
    public const string FIELD_ITEMS_COUNT = 'items_count';
    public const string FIELD_CANCELLABLE = 'is_cancellable';
    /**-@#*/

    /**
     * @param string $number payout reference, used when cancelling
     * @param ?\DateTimeInterface $date when the payout was requested
     * @param string $requestType whether it was requested manually or automatically
     * @param string $payoutType how it is being delivered
     * @param ?string $bankAccountNumber empty or null for a postal payout
     * @param string $company
     * @param string $person
     * @param string $street
     * @param string $postcode
     * @param string $city
     * @param float $fee gross handling fee in PLN
     * @param float $feeNett net handling fee in PLN
     * @param float $feeVatRate
     * @param string $feePaymentType how the fee is being settled
     * @param string $feePaymentStatus whether the fee has been paid
     * @param string $status PENDING while part of the amount is still to be paid out, PAID once none is
     * @param float $amount total requested, in PLN
     * @param float $toPayAmount still to be paid out
     * @param float $paidAmount already paid out
     * @param float $compensationAmount withheld against outstanding debts
     * @param int $itemsCount cash-on-delivery amounts covered by this payout
     * @param bool $cancellable whether it can still be cancelled
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REQUEST_TYPE)]
        private string $requestType = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        private string $payoutType = '',

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT_NUMBER)]
        private ?string $bankAccountNumber = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        private string $company = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        private string $person = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        private string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        private string $postcode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        private string $city = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE)]
        private float $fee = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_NETT)]
        private float $feeNett = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_VAT_RATE)]
        private float $feeVatRate = 0.0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT_TYPE)]
        private string $feePaymentType = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT_STATUS)]
        private string $feePaymentStatus = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private string $status = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        private float $amount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_TO_PAY_AMOUNT)]
        private float $toPayAmount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PAID_AMOUNT)]
        private float $paidAmount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_COMPENSATION_AMOUNT)]
        private float $compensationAmount = 0.0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS_COUNT)]
        private int $itemsCount = 0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_CANCELLABLE)]
        private bool $cancellable = false,
    ) {}

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    /**
     * @return string
     */
    public function getPayoutType(): string
    {
        return $this->payoutType;
    }

    /**
     * @return ?string
     */
    public function getIban(): ?string
    {
        return $this->bankAccountNumber;
    }

    /**
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return float
     */
    public function getFee(): float
    {
        return $this->fee;
    }

    /**
     * @return float
     */
    public function getFeeNett(): float
    {
        return $this->feeNett;
    }

    /**
     * @return float
     */
    public function getFeeVatRate(): float
    {
        return $this->feeVatRate;
    }

    /**
     * @return string
     */
    public function getFeePaymentType(): string
    {
        return $this->feePaymentType;
    }

    /**
     * @return string
     */
    public function getFeePaymentStatus(): string
    {
        return $this->feePaymentStatus;
    }

    /**
     * @return string PENDING or PAID
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function getToPayAmount(): float
    {
        return $this->toPayAmount;
    }

    /**
     * @return float
     */
    public function getPaidAmount(): float
    {
        return $this->paidAmount;
    }

    /**
     * @return float
     */
    public function getCompensationAmount(): float
    {
        return $this->compensationAmount;
    }

    /**
     * @return int
     */
    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    /**
     * @return bool
     */
    public function isCancellable(): bool
    {
        return $this->cancellable;
    }
}
