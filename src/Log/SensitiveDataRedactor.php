<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Request\Account\PinMessage;
use Polkurier\Webservice\Request\BankAccount\SaveMessage as BankAccountSaveMessage;
use Polkurier\Webservice\Request\Common\TokenMessage;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Response\Payment\RegisteredPaymentMessage;

/**
 * Hides secrets in request and response bodies before they are logged.
 *
 * A value is hidden when its key is one of {@see self::FIELDS}, or is named `pin` or ends in
 * `_pin`, at any depth: the API token, passwords, PINs, two-factor codes and access tokens.
 * Matching is by key alone, so a field that shares a name with a secret, such as a coupon's `code`,
 * is hidden too.
 */
class SensitiveDataRedactor extends FieldRedactor
{
    /**
     * Wire names of the fields whose values are hidden.
     */
    public const array FIELDS = [
        TokenMessage::FIELD_TOKEN,
        RegisteredPaymentMessage::FIELD_ACCESS_TOKEN,
        ChangePasswordMessage::FIELD_PASSWORD,
        ChangePasswordMessage::FIELD_PREVIOUS_PASSWORD,
        PinMessage::FIELD_PIN,
        CreateMoneyBoxPayoutMessage::FIELD_SECURITY_PIN,
        BankAccountSaveMessage::FIELD_ACCESS_PIN,
        CreateMoneyBoxPayoutMessage::FIELD_TWO_FACTOR_CODE,
        VerifyMessage::FIELD_CODE,
    ];

    /**
     * @param string[] $fields wire names of the fields to hide
     */
    public function __construct(array $fields = self::FIELDS)
    {
        parent::__construct($fields);
    }

    /**
     * A key named `pin` or ending in `_pin` is hidden too, in any message.
     */
    protected function hides(string $key, array $fields): bool
    {
        return parent::hides($key, $fields) || $key === 'pin' || \str_ends_with($key, '_pin');
    }
}
