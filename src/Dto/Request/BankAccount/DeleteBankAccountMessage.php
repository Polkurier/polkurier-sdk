<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\BankAccount;

use Polkurier\Webservice\Dto\Request\Common\IdMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies a bank account to delete.
 *
 * @api
 */
final readonly class DeleteBankAccountMessage extends IdMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ACCESS_PIN = 'access_pin';
    /**-@#*/

    /**
     * @param string $id
     * @param string|null $accessPin required only while the bank-account PIN lock is engaged
     */
    public function __construct(
        string $id,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ACCESS_PIN)]
        #[OmitWhenEmpty]
        public ?string $accessPin = null,
    ) {
        parent::__construct($id);
    }
}
