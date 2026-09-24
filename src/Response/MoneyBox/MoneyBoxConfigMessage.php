<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The account's standing money box payout arrangement.
 *
 * Read this before offering a payout: it says which payout method the account is set up for, which
 * fee settlement options it may use, and what destination details are already on file. The
 * destination fields are populated for whichever payout type applies and blank for the other.
 */
readonly class MoneyBoxConfigMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAYOUT_TYPE = 'payout_type';
    public const string FIELD_FEE_PAYMENT = 'fee_payment';
    public const string FIELD_FEE_PAYMENTS_TYPES_AVAILABLE = 'fee_payments_types_available';
    public const string FIELD_BANK_ACCOUNT_NUMBER = 'bank_account_number';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_PERSON = 'person';
    public const string FIELD_STREET = 'street';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_CITY = 'city';
    /**-@#*/

    /**
     * @param MoneyBoxPayoutTypeEnum $payoutType how payouts are delivered
     * @param MoneyBoxFeePaymentTypeEnum $feePayment how the handling fee is settled by default
     * @param MoneyBoxFeePaymentTypeEnum[] $feePaymentsTypesAvailable fee settlement options this account may choose from
     * @param string $bankAccountNumber destination account for bank transfers
     * @param string $company postal payout recipient
     * @param string $person postal payout recipient
     * @param string $street postal payout address
     * @param string $postcode postal payout address
     * @param string $city postal payout address
     */
    public function __construct(
        /**
         * @var MoneyBoxPayoutTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        private MoneyBoxPayoutTypeEnum $payoutType = MoneyBoxPayoutTypeEnum::Account,

        /**
         * @var MoneyBoxFeePaymentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT)]
        private MoneyBoxFeePaymentTypeEnum $feePayment = MoneyBoxFeePaymentTypeEnum::Invoice,

        /**
         * @var MoneyBoxFeePaymentTypeEnum[]
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENTS_TYPES_AVAILABLE)]
        private array $feePaymentsTypesAvailable = [],

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT_NUMBER)]
        private string $bankAccountNumber = '',

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
    ) {}

    /**
     * @return MoneyBoxPayoutTypeEnum
     */
    public function getPayoutType(): MoneyBoxPayoutTypeEnum
    {
        return $this->payoutType;
    }

    /**
     * @return MoneyBoxFeePaymentTypeEnum
     */
    public function getFeePayment(): MoneyBoxFeePaymentTypeEnum
    {
        return $this->feePayment;
    }

    /**
     * @return MoneyBoxFeePaymentTypeEnum[]
     */
    public function getFeePaymentsTypesAvailable(): array
    {
        return $this->feePaymentsTypesAvailable;
    }

    /**
     * @return string
     */
    public function getIban(): string
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
}
