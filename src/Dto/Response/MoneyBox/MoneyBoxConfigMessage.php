<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The account's standing money box payout arrangement.
 *
 * Read this before offering a payout. It gives the account's payout method, the fee settlement
 * options it may use and the destination details on file. A destination with nothing saved comes
 * back blank.
 */
final readonly class MoneyBoxConfigMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param MoneyBoxPayoutTypeEnum|null $payoutType how payouts are delivered
     * @param MoneyBoxFeePaymentTypeEnum|null $feePayment how the handling fee is settled by default
     * @param MoneyBoxFeePaymentTypeEnum[] $feePaymentsTypesAvailable fee settlement options this account may
     *        choose from
     * @param string $bankAccountNumber destination account for bank transfers
     * @param string $company postal payout recipient
     * @param string $person postal payout recipient
     * @param string $street postal payout address
     * @param string $postcode postal payout address
     * @param string $city postal payout address
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var MoneyBoxPayoutTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        private ?MoneyBoxPayoutTypeEnum $payoutType = MoneyBoxPayoutTypeEnum::Account,

        /**
         * @var MoneyBoxFeePaymentTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT)]
        private ?MoneyBoxFeePaymentTypeEnum $feePayment = MoneyBoxFeePaymentTypeEnum::Invoice,

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

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return MoneyBoxPayoutTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getPayoutType(): ?MoneyBoxPayoutTypeEnum
    {
        return $this->payoutType;
    }

    /**
     * @return MoneyBoxFeePaymentTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getFeePayment(): ?MoneyBoxFeePaymentTypeEnum
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
