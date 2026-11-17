<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\BankAccount;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Common\TwoFactorFieldsInterface;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Registers or updates a bank account, with the confirmation the API may require.
 *
 * While `get_account_info` reports the bank-account PIN lock on, every save needs the access PIN.
 * Otherwise registering, or changing the number or default flag, needs a confirmed two-factor
 * token; renaming needs none.
 *
 * @api
 */
final readonly class SaveBankAccountMessage implements MessageInterface, TwoFactorFieldsInterface
{

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_BANK_ACCOUNT = 'bank_account';
    public const string FIELD_ACCESS_PIN = 'access_pin';
    /**-@#*/

    // `bank_account` names a field marked #[InlineFields], whose entries are written at this
    // level, so the key itself never reaches the payload.

    /**
     * @param BankAccount $bankAccount the account to save. Its fields are sent at the top level
     * @param string|null $twoFactorToken
     * @param string|null $twoFactorCode
     * @param string|null $accessPin required only while the bank-account PIN lock is engaged
     */
    public function __construct(
        /**
         * @var BankAccount
         */
        #[Serializer\SerializedName(self::FIELD_BANK_ACCOUNT)]
        #[InlineFields]
        #[Validate\Valid]
        public BankAccount $bankAccount,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_TOKEN)]
        #[OmitWhenEmpty]
        public ?string $twoFactorToken = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_TWO_FACTOR_CODE)]
        #[OmitWhenEmpty]
        public ?string $twoFactorCode = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ACCESS_PIN)]
        #[OmitWhenEmpty]
        public ?string $accessPin = null,
    ) {}
}
