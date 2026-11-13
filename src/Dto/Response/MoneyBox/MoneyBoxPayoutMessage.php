<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
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
final readonly class MoneyBoxPayoutMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
    public const string FIELD_FEE_NET = 'fee_nett';
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
     * @param \DateTimeInterface|null $date when the payout was requested
     * @param string $requestType whether it was requested manually or automatically
     * @param string $payoutType how it is being delivered
     * @param string|null $bankAccountNumber empty or null for a postal payout
     * @param string $company
     * @param string $person
     * @param string $street
     * @param string $postcode
     * @param string $city
     * @param float $fee gross handling fee in PLN
     * @param float $feeNet net handling fee in PLN
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
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        public string $number = '',

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $date = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REQUEST_TYPE)]
        public string $requestType = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        public string $payoutType = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT_NUMBER)]
        public ?string $bankAccountNumber = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        public string $company = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        public string $person = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        public string $street = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        public string $postcode = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        public string $city = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE)]
        public float $fee = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_NET)]
        public float $feeNet = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_FEE_VAT_RATE)]
        public float $feeVatRate = 0.0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT_TYPE)]
        public string $feePaymentType = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT_STATUS)]
        public string $feePaymentStatus = '',

        /**
         * @var string PENDING or PAID
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public string $status = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        public float $amount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_TO_PAY_AMOUNT)]
        public float $toPayAmount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PAID_AMOUNT)]
        public float $paidAmount = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_COMPENSATION_AMOUNT)]
        public float $compensationAmount = 0.0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS_COUNT)]
        public int $itemsCount = 0,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_CANCELLABLE)]
        public bool $cancellable = false,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
