<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\MoneyBox;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\Common\TwoFactorFieldsTrait;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxFeePaymentTypeEnum;
use Polkurier\Webservice\Enum\MoneyBox\MoneyBoxPayoutTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Requests a payout from the money box balance.
 *
 * Moves money and is not idempotent. After a timeout, check {@see
 * \Polkurier\Webservice\Api\Service\MoneyBoxServiceInterface::getPayouts()} before retrying.
 *
 * The payout type, ACCOUNT by default, decides the destination: a saved bank account's number, or a
 * postal address. Nothing is filled in from `get_money_box_config`.
 *
 * Only a postal money order to an address other than the saved default needs a confirmation: the
 * security PIN while the money box is PIN-locked, otherwise a two-factor token registered under
 * MONEY_BOX_PAYOUT.
 */
readonly class CreateMoneyBoxPayoutMessage implements MessageInterface
{
    use TwoFactorFieldsTrait;

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
     * @param ?MoneyBoxPayoutTypeEnum $payoutType delivery method, ACCOUNT when left out
     * @param ?MoneyBoxFeePaymentTypeEnum $feePaymentType how to pay the handling fee, one of those
     *        `get_money_box_config` lists as available
     * @param ?string $bankAccountNumber for a bank transfer, one of the account's saved bank accounts
     * @param ?string $company for a postal money order
     * @param ?string $person for a postal money order
     * @param ?string $street for a postal money order
     * @param ?string $postcode for a postal money order
     * @param ?string $city for a postal money order
     * @param ?string $securityPin for a money order to another address while the money box PIN lock is engaged
     * @param ?string $twoFactorToken for a money order to another address without the PIN lock, registered under
     *        MONEY_BOX_PAYOUT
     * @param ?string $twoFactorFormId form identifier issued with the token
     * @param ?string $twoFactorCode the code the customer entered
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[OmitWhenEmpty]
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
