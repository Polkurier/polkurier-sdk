<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The account's profile, balances and outstanding obligations.
 *
 * These fields decide what an integration may do next:
 *
 * - Acceptance flags. Orders are refused for a customer who has not accepted the current terms, the
 *   price list or the carriers' terms. `is_new_regulations` means revised terms are waiting to be
 *   accepted.
 * - Verification flags. On some accounts, `require_account_verification` and
 *   `require_agreement_verification` block shipping entirely.
 * - PIN locks: which calls need the security PIN attached.
 *
 * Balances are in PLN. The money box has 3 of them: the total held, the part you can withdraw now,
 * and the part already committed to a payout.
 */
readonly class AccountInfoMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_DISPLAY_NAME = 'display_name';
    public const string FIELD_EMAIL = 'email';
    public const string FIELD_DEFAULT_COURIER = 'default_courier';
    public const string FIELD_ACTIVE = 'is_active';
    public const string FIELD_REGULATIONS_ACCEPTED = 'is_regulations_accepted';
    public const string FIELD_NEW_REGULATIONS = 'is_new_regulations';
    public const string FIELD_PRICES_LIST_ACCEPTED = 'is_prices_list_accepted';
    public const string FIELD_COURIERS_REGULATIONS_ACCEPTED = 'is_couriers_regulations_accepted';
    public const string FIELD_ELECTRONIC_INVOICE_ACCEPTED = 'is_electronic_invoice_accepted';
    public const string FIELD_EMAIL_VERIFIED = 'is_email_verified';
    public const string FIELD_PAYER_DATA_COMPLETE = 'is_payer_data_complete';
    public const string FIELD_VERIFIED = 'is_verified';
    public const string FIELD_DELETE_REQUESTED = 'is_delete_requested';
    public const string FIELD_HAS_PHONE_NUMBER = 'has_phone_number';
    public const string FIELD_REQUIRE_ACCOUNT_VERIFICATION = 'require_account_verification';
    public const string FIELD_REQUIRE_AGREEMENT_VERIFICATION = 'require_agreement_verification';
    public const string FIELD_PAYMENT_TYPE = 'payment_type';
    public const string FIELD_MONEY_BOX_BALANCE = 'money_box_balance';
    public const string FIELD_MONEY_BOX_PAYOUT_AVAILABLE_BALANCE = 'money_box_payout_available_balance';
    public const string FIELD_MONEY_BOX_TO_PAYOUT_BALANCE = 'money_box_to_payout_balance';
    public const string FIELD_PREPAID_BALANCE = 'prepaid_balance';
    public const string FIELD_INVOICE_BALANCE = 'invoice_balance';
    public const string FIELD_INVOICE_PAYMENT_ACCOUNT_NUMBER = 'invoice_payment_account_number';
    public const string FIELD_INVOICE_LIMIT = 'invoice_limit';
    public const string FIELD_INVOICE_LIMIT_USED = 'invoice_limit_used';
    public const string FIELD_INVOICE_REBATE = 'invoice_rebate';
    public const string FIELD_BONUSES_SAVINGS = 'bonuses_savings';
    public const string FIELD_DEFAULT_SENDER_POINT = 'default_sender_point';
    public const string FIELD_PIN_LOCKS = 'pin_locks';
    public const string FIELD_SETTINGS = 'settings';
    public const string FIELD_EXTERNAL_APPLICATION_SETTINGS = 'external_application_settings';
    /**-@#*/

    /**
     * @param int $id the numeric account id, the same value used as the API login
     * @param string $displayName
     * @param string $email
     * @param string $defaultCourier carrier code preselected on the order form, empty if none
     * @param bool $active an inactive account cannot authenticate
     * @param bool $regulationsAccepted
     * @param bool $newRegulations revised terms are waiting to be accepted
     * @param bool $pricesListAccepted
     * @param bool $couriersRegulationsAccepted
     * @param bool $electronicInvoiceAccepted
     * @param bool $emailVerified
     * @param bool $payerDataComplete the billing address is complete enough to invoice
     * @param bool $verified
     * @param bool $deleteRequested account deletion has been requested
     * @param bool $hasPhoneNumber
     * @param bool $requireAccountVerification shipping is blocked until the account is verified
     * @param bool $requireAgreementVerification shipping is blocked until an agreement is signed
     * @param string $paymentType how the account settles, e.g. INVOICE or PREPAID
     * @param float $moneyBoxBalance total held in the money box, in PLN
     * @param float $moneyBoxPayoutAvailableBalance the part that can be withdrawn now
     * @param float $moneyBoxToPayoutBalance the part already committed to a pending payout
     * @param float $prepaidBalance
     * @param float $invoiceBalance amount currently owed on invoices
     * @param string|null $invoicePaymentAccountNumber account to pay invoices into
     * @param float|null $invoiceLimit credit limit, or null when the account has none
     * @param float|null $invoiceLimitUsed how much of that limit is used
     * @param float $invoiceRebate standing discount, as a percentage
     * @param float $bonusesSavings cumulative saving from bonuses, in PLN
     * @param array<string, string|null> $defaultSenderPoint default drop-off point per carrier code, null
     *        for carriers with none set
     * @param PinLocksMessage|null $pinLocks
     * @param AccountSettingsMessage|null $settings
     * @param array<string, mixed>|null $externalApplicationSettings the external application's
     *        settings by name, present only when the request named one
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private int $id = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DISPLAY_NAME)]
        private string $displayName = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        private string $email = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_COURIER)]
        private string $defaultCourier = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_ACTIVE)]
        private bool $active = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REGULATIONS_ACCEPTED)]
        private bool $regulationsAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_NEW_REGULATIONS)]
        private bool $newRegulations = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PRICES_LIST_ACCEPTED)]
        private bool $pricesListAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS_REGULATIONS_ACCEPTED)]
        private bool $couriersRegulationsAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_ELECTRONIC_INVOICE_ACCEPTED)]
        private bool $electronicInvoiceAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL_VERIFIED)]
        private bool $emailVerified = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAYER_DATA_COMPLETE)]
        private bool $payerDataComplete = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_VERIFIED)]
        private bool $verified = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DELETE_REQUESTED)]
        private bool $deleteRequested = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_PHONE_NUMBER)]
        private bool $hasPhoneNumber = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_ACCOUNT_VERIFICATION)]
        private bool $requireAccountVerification = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_AGREEMENT_VERIFICATION)]
        private bool $requireAgreementVerification = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PAYMENT_TYPE)]
        private string $paymentType = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_BALANCE)]
        private float $moneyBoxBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_PAYOUT_AVAILABLE_BALANCE)]
        private float $moneyBoxPayoutAvailableBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_TO_PAYOUT_BALANCE)]
        private float $moneyBoxToPayoutBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PREPAID_BALANCE)]
        private float $prepaidBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_BALANCE)]
        private float $invoiceBalance = 0.0,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_PAYMENT_ACCOUNT_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $invoicePaymentAccountNumber = null,

        /**
         * @var ?float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_LIMIT)]
        #[OmitWhenEmpty]
        private ?float $invoiceLimit = null,

        /**
         * @var ?float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_LIMIT_USED)]
        #[OmitWhenEmpty]
        private ?float $invoiceLimitUsed = null,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_REBATE)]
        private float $invoiceRebate = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_BONUSES_SAVINGS)]
        private float $bonusesSavings = 0.0,

        /**
         * @var array<string, string|null>
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_SENDER_POINT)]
        private array $defaultSenderPoint = [],

        /**
         * @var ?PinLocksMessage
         */
        #[Serializer\SerializedName(self::FIELD_PIN_LOCKS)]
        private ?PinLocksMessage $pinLocks = null,

        /**
         * @var ?AccountSettingsMessage
         */
        #[Serializer\SerializedName(self::FIELD_SETTINGS)]
        private ?AccountSettingsMessage $settings = null,

        /**
         * @var array<string, mixed>|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_APPLICATION_SETTINGS)]
        #[OmitWhenEmpty]
        private ?array $externalApplicationSettings = null,
    ) {}

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getDefaultCourier(): string
    {
        return $this->defaultCourier;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isRegulationsAccepted(): bool
    {
        return $this->regulationsAccepted;
    }

    /**
     * @return bool
     */
    public function isNewRegulations(): bool
    {
        return $this->newRegulations;
    }

    /**
     * @return bool
     */
    public function isPricesListAccepted(): bool
    {
        return $this->pricesListAccepted;
    }

    /**
     * @return bool
     */
    public function isCouriersRegulationsAccepted(): bool
    {
        return $this->couriersRegulationsAccepted;
    }

    /**
     * @return bool
     */
    public function isElectronicInvoiceAccepted(): bool
    {
        return $this->electronicInvoiceAccepted;
    }

    /**
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    /**
     * @return bool
     */
    public function isPayerDataComplete(): bool
    {
        return $this->payerDataComplete;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * @return bool
     */
    public function isDeleteRequested(): bool
    {
        return $this->deleteRequested;
    }

    /**
     * @return bool
     */
    public function hasPhoneNumber(): bool
    {
        return $this->hasPhoneNumber;
    }

    /**
     * @return bool
     */
    public function isRequireAccountVerification(): bool
    {
        return $this->requireAccountVerification;
    }

    /**
     * @return bool
     */
    public function isRequireAgreementVerification(): bool
    {
        return $this->requireAgreementVerification;
    }

    /**
     * @return string
     */
    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return float
     */
    public function getMoneyBoxBalance(): float
    {
        return $this->moneyBoxBalance;
    }

    /**
     * @return float
     */
    public function getMoneyBoxPayoutAvailableBalance(): float
    {
        return $this->moneyBoxPayoutAvailableBalance;
    }

    /**
     * @return float
     */
    public function getMoneyBoxToPayoutBalance(): float
    {
        return $this->moneyBoxToPayoutBalance;
    }

    /**
     * @return float
     */
    public function getPrepaidBalance(): float
    {
        return $this->prepaidBalance;
    }

    /**
     * @return float
     */
    public function getInvoiceBalance(): float
    {
        return $this->invoiceBalance;
    }

    /**
     * @return string|null
     */
    public function getInvoicePaymentAccountNumber(): ?string
    {
        return $this->invoicePaymentAccountNumber;
    }

    /**
     * @return float|null
     */
    public function getInvoiceLimit(): ?float
    {
        return $this->invoiceLimit;
    }

    /**
     * @return float|null
     */
    public function getInvoiceLimitUsed(): ?float
    {
        return $this->invoiceLimitUsed;
    }

    /**
     * @return float
     */
    public function getInvoiceRebate(): float
    {
        return $this->invoiceRebate;
    }

    /**
     * @return float
     */
    public function getBonusesSavings(): float
    {
        return $this->bonusesSavings;
    }

    /**
     * @return array<string, string|null> default drop-off point per carrier code, null for carriers
     *         with none set
     */
    public function getDefaultSenderPoint(): array
    {
        return $this->defaultSenderPoint;
    }

    /**
     * @return PinLocksMessage|null null if the response leaves it out
     */
    public function getPinLocks(): ?PinLocksMessage
    {
        return $this->pinLocks;
    }

    /**
     * @return AccountSettingsMessage|null null if the response leaves it out
     */
    public function getSettings(): ?AccountSettingsMessage
    {
        return $this->settings;
    }

    /**
     * The settings of the external application the request named, one entry per setting.
     *
     * Empty when the application has none, and also when the id doesn't match one of the
     * account's applications, which isn't reported as an error.
     *
     * @return ExternalApplicationSettingMessage[]|null null when the request named no application
     */
    public function getExternalApplicationSettings(): ?array
    {
        if ($this->externalApplicationSettings === null) {
            return null;
        }

        $settings = [];
        foreach ($this->externalApplicationSettings as $name => $value) {
            $settings[] = new ExternalApplicationSettingMessage(
                (string)$name,
                $value === null || \is_scalar($value) ? $value : (string)\json_encode($value, \JSON_UNESCAPED_UNICODE),
            );
        }

        return $settings;
    }
}
