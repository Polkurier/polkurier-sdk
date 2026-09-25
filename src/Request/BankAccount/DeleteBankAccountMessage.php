<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\BankAccount;

use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies a bank account to delete.
 *
 * @see DeleteBankAccountMessage
 */
readonly class DeleteBankAccountMessage extends DeleteMessage
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
        private ?string $accessPin = null,
    ) {
        parent::__construct($id);
    }

    /**
     * The account's security PIN, required only while the bank-account lock is engaged.
     *
     * @return string|null
     */
    public function getAccessPin(): ?string
    {
        return $this->accessPin;
    }
}
