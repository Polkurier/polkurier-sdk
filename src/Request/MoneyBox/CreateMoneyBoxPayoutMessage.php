<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\Common\TwoFactorFields;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Requests a payout from the money box balance.
 *
 * This moves money and is not idempotent: each accepted call creates a separate payout request. If
 * a call times out, its outcome is unknown. Check
 * {@see \Polkurier\Webservice\Api\Service\MoneyBoxServiceInterface::getPayouts()} before retrying.
 *
 * The destination fields depend on the payout type. A bank transfer needs the account number. A
 * money order needs the postal address. Fields you leave out default to the values in
 * `get_money_box_config`.
 *
 * The security PIN is required while the account's money box settings are PIN-locked. A verified
 * two-factor token registered under MONEY_BOX_PAYOUT is required as well.
 */
readonly class CreateMoneyBoxPayoutMessage implements MessageInterface
{
    use TwoFactorFields;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDERS = 'orders';
    public const string FIELD_PAYOUT_TYPE = 'payout_type';
    public const string FIELD_FEE_PAYMENT_TYPE = 'fee_payment_type';
    public const string FIELD_BANK_ACCOUNT_NUMBER = 'bank_account_number';
    public const string FIELD_COMPANY = 'company';
    public const string FIELD_PERSON = 'person';
    public const string FIELD_STREET = 'street';
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_CITY = 'city';
    public const string FIELD_SECURITY_PIN = 'security_pin';
    public const string FIELD_TWO_FACTOR_FORM_ID = 'two_factor_form_id';
    /**-@#*/

    /**
     * @param string[] $orders order numbers whose cash-on-delivery amounts to pay out, empty for everything available
     * @param ?MoneyBoxPayoutTypeEnum $payoutType delivery method, defaults to the account's configured one
     * @param ?MoneyBoxFeePaymentTypeEnum $feePaymentType how to pay the handling fee, must be one the account can use
     * @param ?string $bankAccountNumber for a bank transfer
     * @param ?string $company for a postal money order
     * @param ?string $person for a postal money order
     * @param ?string $street for a postal money order
     * @param ?string $postcode for a postal money order
     * @param ?string $city for a postal money order
     * @param ?string $securityPin required while the money box PIN lock is engaged
     * @param ?string $twoFactorToken token registered under MONEY_BOX_PAYOUT
     * @param ?string $twoFactorFormId form identifier issued with the token
     * @param ?string $twoFactorCode the code the customer entered
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[Validate\Each(Validate\OrderNumber::class)]
        private array $orders = [],

        /**
         * @var ?MoneyBoxPayoutTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_PAYOUT_TYPE)]
        #[OmitWhenEmpty]
        private ?MoneyBoxPayoutTypeEnum $payoutType = null,

        /**
         * @var ?MoneyBoxFeePaymentTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_FEE_PAYMENT_TYPE)]
        #[OmitWhenEmpty]
        private ?MoneyBoxFeePaymentTypeEnum $feePaymentType = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT_NUMBER)]
        #[Validate\Iban]
        #[OmitWhenEmpty]
        private ?string $bankAccountNumber = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_COMPANY)]
        #[OmitWhenEmpty]
        private ?string $company = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_PERSON)]
        #[OmitWhenEmpty]
        private ?string $person = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_STREET)]
        #[OmitWhenEmpty]
        private ?string $street = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[Validate\Postcode]
        #[OmitWhenEmpty]
        private ?string $postcode = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_CITY)]
        #[OmitWhenEmpty]
        private ?string $city = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_SECURITY_PIN)]
        #[OmitWhenEmpty]
        private ?string $securityPin = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_TOKEN)]
        #[OmitWhenEmpty]
        private ?string $twoFactorToken = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_FORM_ID)]
        #[OmitWhenEmpty]
        private ?string $twoFactorFormId = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE)]
        #[OmitWhenEmpty]
        private ?string $twoFactorCode = null,
    ) {}

    /**
     * @return string[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return ?MoneyBoxPayoutTypeEnum
     */
    public function getPayoutType(): ?MoneyBoxPayoutTypeEnum
    {
        return $this->payoutType;
    }

    /**
     * @return ?MoneyBoxFeePaymentTypeEnum
     */
    public function getFeePaymentType(): ?MoneyBoxFeePaymentTypeEnum
    {
        return $this->feePaymentType;
    }

    /**
     * @return ?string
     */
    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    /**
     * @return ?string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return ?string
     */
    public function getPerson(): ?string
    {
        return $this->person;
    }

    /**
     * @return ?string
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @return ?string
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @return ?string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return ?string
     */
    public function getSecurityPin(): ?string
    {
        return $this->securityPin;
    }

    /**
     * @return ?string
     */
    public function getTwoFactorFormId(): ?string
    {
        return $this->twoFactorFormId;
    }
}
