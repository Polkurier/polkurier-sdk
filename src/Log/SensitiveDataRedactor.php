<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Dto\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Dto\Request\Common\TokenMessage;
use Polkurier\Webservice\Dto\Request\Common\TwoFactorFieldsInterface;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Dto\Response\Payment\RegisteredPaymentMessage;

/**
 * Hides secrets in request and response bodies before they are logged.
 *
 * A value is hidden when its key is one of {@see self::FIELDS}, or is named `pin` or ends in
 * `_pin`, at any depth: the API token, passwords, PINs, two-factor codes and tokens, and
 * access tokens.
 * Matching is by key alone, so a field that shares a name with a secret, such as a coupon's `code`,
 * is hidden too.
 */
class SensitiveDataRedactor extends FieldRedactor
{
    /**
     * Wire names of the fields whose values are hidden.
     *
     * A key named `pin` or ending in `_pin` is not listed: {@see self::hides()} hides every one of
     * them, in any message, so naming the three the SDK happens to declare would add nothing.
     */
    public const array FIELDS = [
        TokenMessage::FIELD_TOKEN,
        RegisteredPaymentMessage::FIELD_ACCESS_TOKEN,
        ChangePasswordMessage::FIELD_PASSWORD,
        ChangePasswordMessage::FIELD_PREVIOUS_PASSWORD,
        TwoFactorFieldsInterface::FIELD_TWO_FACTOR_CODE,
        TwoFactorFieldsInterface::FIELD_TWO_FACTOR_TOKEN,
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
