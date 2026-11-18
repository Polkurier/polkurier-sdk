<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Which operations the account has put behind its security PIN.
 *
 * When a lock is on, the matching operation takes the security PIN instead of a two-factor code.
 *
 * @api
 */
final readonly class PinLocksMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PIN_SETTINGS = 'PIN_SETTINGS';
    public const string FIELD_BANK_ACCOUNTS_EDIT = 'BANK_ACCOUNTS_EDIT';
    public const string FIELD_COD_MONEY_BOX = 'COD_MONEY_BOX';
    /**-@#*/

    /**
     * @param bool $pinSettings changing which operations are locked is itself locked; true whenever
     *        the account has a security PIN
     * @param bool $bankAccountsEdit adding, changing or removing a bank account is locked
     * @param bool $codMoneyBox money box settings and payouts are locked
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_PIN_SETTINGS)]
        public bool $pinSettings = false,

        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNTS_EDIT)]
        public bool $bankAccountsEdit = false,

        #[Serializer\SerializedName(self::FIELD_COD_MONEY_BOX)]
        public bool $codMoneyBox = false,
    ) {}
}
