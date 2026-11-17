<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Serializer\Attribute\NamedValues;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The account's profile, balances and outstanding obligations.
 *
 * These fields decide what an integration may do next:
 *
 * - `is_email_verified` and `is_delete_requested`. Orders are refused while the e-mail address is
 *   unverified or deletion has been requested.
 * - Acceptance flags. `is_new_regulations` means revised terms are waiting to be accepted.
 * - `require_agreement_verification`: the account has to complete identity verification.
 * - PIN locks: which operations take the security PIN instead of a two-factor code.
 *
 * Balances are in PLN. The money box has 3 of them: the total held, the part you can withdraw now,
 * and the part already committed to a payout.
 *
 * @api
 */
final readonly class AccountInfoMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
    public const string FIELD_PHONE_PIN = 'phone_pin';
    public const string FIELD_DEFAULT_INPOST_SENDER_POINT = 'default_shipping_point_sender_INPOST_PACZKOMAT';
    public const string FIELD_DEFAULT_KURIER48_SENDER_POINT = 'default_shipping_point_sender_KURIER48';
    /**-@#*/

    /**
     * @param int $id the numeric account id, the same value used as the API login
     * @param string $displayName
     * @param string $email
     * @param string $defaultCourier carrier code preselected on the order form, empty if none
     * @param bool $active an inactive account cannot authenticate
     * @param bool $regulationsAccepted false once acceptance of the terms is due: none accepted yet,
     *        or the current ones unaccepted past the acceptance period
     * @param bool $newRegulations revised terms are waiting to be accepted, reported in one response
     *        only
     * @param bool $pricesListAccepted always equal to $electronicInvoiceAccepted
     * @param bool $couriersRegulationsAccepted
     * @param bool $electronicInvoiceAccepted always equal to $pricesListAccepted
     * @param bool $emailVerified orders are refused while false
     * @param bool $payerDataComplete the billing address is complete enough to invoice
     * @param bool $verified hard-coded true by the API
     * @param bool $deleteRequested account deletion has been requested; orders are refused while
     *        true
     * @param bool $hasPhoneNumber
     * @param bool $requireAccountVerification hard-coded false by the API
     * @param bool $requireAgreementVerification the account has to complete identity verification
     * @param string $paymentType how the account settles: INVOICE or PREPAID
     * @param float $moneyBoxBalance total held in the money box, in PLN
     * @param float $moneyBoxPayoutAvailableBalance the part that can be withdrawn now
     * @param float $moneyBoxToPayoutBalance the part already committed to a pending payout
     * @param float $prepaidBalance
     * @param float $invoiceBalance amount currently owed on invoices
     * @param string|null $invoicePaymentAccountNumber account to pay invoices into
     * @param float|null $invoiceLimit credit limit, or null when the account has none
     * @param float|null $invoiceLimitUsed how much of that limit is used
     * @param float $invoiceRebate the invoice rebate balance, in PLN
     * @param float $bonusesSavings how much the account has saved by shipping with
     *        `INPOST_PACZKOMAT_ECONOMY` instead of the standard service, in PLN
     * @param array<string, string|null> $defaultSenderPoint default drop-off point per carrier code, null
     *        for carriers with none set
     * @param PinLocksMessage|null $pinLocks
     * @param AccountSettingsMessage|null $settings
     * @param ExternalApplicationSettingMessage[]|null $externalApplicationSettings the settings of
     *        the external application the request named
     * @param string $phonePin the phone PIN, the code given when contacting support, empty if
     *        none is set
     * @param string|null $defaultInpostSenderPoint the `INPOST_PACZKOMAT` entry of
     *        $defaultSenderPoint
     * @param string|null $defaultKurier48SenderPoint the point the API reports under
     *        `default_shipping_point_sender_KURIER48`, which names no carrier code the SDK
     *        knows; read $defaultSenderPoint to address a carrier by its code
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public int $id = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DISPLAY_NAME)]
        #[PersonalData]
        public string $displayName = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL)]
        #[PersonalData]
        public string $email = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_COURIER)]
        public string $defaultCourier = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_ACTIVE)]
        public bool $active = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REGULATIONS_ACCEPTED)]
        public bool $regulationsAccepted = false,

        /**
         * Revised terms are waiting to be accepted. True in one response per version of the terms: the
         * call that reports it marks the notice as shown, and later calls answer false, accepted or not.
         *
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_NEW_REGULATIONS)]
        public bool $newRegulations = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PRICES_LIST_ACCEPTED)]
        public bool $pricesListAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS_REGULATIONS_ACCEPTED)]
        public bool $couriersRegulationsAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_ELECTRONIC_INVOICE_ACCEPTED)]
        public bool $electronicInvoiceAccepted = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_EMAIL_VERIFIED)]
        public bool $emailVerified = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_PAYER_DATA_COMPLETE)]
        public bool $payerDataComplete = false,

        /**
         * @var bool
         * @deprecated The API hard-codes this to true; it says nothing about the account.
         */
        #[Serializer\SerializedName(self::FIELD_VERIFIED)]
        public bool $verified = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DELETE_REQUESTED)]
        public bool $deleteRequested = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_PHONE_NUMBER)]
        public bool $hasPhoneNumber = false,

        /**
         * @var bool
         * @deprecated The API hard-codes this to false; it says nothing about the account.
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_ACCOUNT_VERIFICATION)]
        public bool $requireAccountVerification = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_AGREEMENT_VERIFICATION)]
        public bool $requireAgreementVerification = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PAYMENT_TYPE)]
        public string $paymentType = '',

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_BALANCE)]
        public float $moneyBoxBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_PAYOUT_AVAILABLE_BALANCE)]
        public float $moneyBoxPayoutAvailableBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_MONEY_BOX_TO_PAYOUT_BALANCE)]
        public float $moneyBoxToPayoutBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_PREPAID_BALANCE)]
        public float $prepaidBalance = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_BALANCE)]
        public float $invoiceBalance = 0.0,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_PAYMENT_ACCOUNT_NUMBER)]
        #[OmitWhenEmpty]
        #[PersonalData]
        public ?string $invoicePaymentAccountNumber = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_LIMIT)]
        #[OmitWhenEmpty]
        public ?float $invoiceLimit = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_LIMIT_USED)]
        #[OmitWhenEmpty]
        public ?float $invoiceLimitUsed = null,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_REBATE)]
        public float $invoiceRebate = 0.0,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_BONUSES_SAVINGS)]
        public float $bonusesSavings = 0.0,

        /**
         * @var array<string, string|null> default drop-off point per carrier code, null for carriers with none set
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_SENDER_POINT)]
        public array $defaultSenderPoint = [],

        /**
         * @var PinLocksMessage|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_PIN_LOCKS)]
        public ?PinLocksMessage $pinLocks = null,

        /**
         * @var AccountSettingsMessage|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_SETTINGS)]
        public ?AccountSettingsMessage $settings = null,

        /**
         * The settings of the external application the request named, one entry per setting.
         *
         * Empty when the application has none, and also when the id doesn't match one of the
         * account's applications, which isn't reported as an error.
         *
         * @var ExternalApplicationSettingMessage[]|null null when the request named no application
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_APPLICATION_SETTINGS)]
        #[NamedValues(
            name: ExternalApplicationSettingMessage::FIELD_NAME,
            value: ExternalApplicationSettingMessage::FIELD_VALUE,
        )]
        public ?array $externalApplicationSettings = null,

        /**
         * The phone PIN, given when contacting support. Not the security PIN.
         *
         * @var string empty when none is set
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_PIN)]
        #[Sensitive]
        public string $phonePin = '',

        /**
         * @var string|null the `INPOST_PACZKOMAT` entry of $defaultSenderPoint, repeated by the API
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_INPOST_SENDER_POINT)]
        public ?string $defaultInpostSenderPoint = null,

        /**
         * @var string|null the point reported under `default_shipping_point_sender_KURIER48`
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT_KURIER48_SENDER_POINT)]
        public ?string $defaultKurier48SenderPoint = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
