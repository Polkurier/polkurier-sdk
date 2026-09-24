<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The state of a payment.
 *
 * The hash is the provider's own signature over the transaction and is there for reconciliation
 * against a provider-side callback, not for display.
 */
readonly class PaymentInfoMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TRANSACTION_ID = 'transaction_id';
    public const string FIELD_STATE = 'state';
    public const string FIELD_LOGIN_ID = 'login_id';
    public const string FIELD_HASH = 'hash';
    /**-@#*/

    /**
     * @param string $transactionId
     * @param string $state where the payment has got to
     * @param ?int $loginId the account the payment belongs to
     * @param ?string $hash the provider's signature over the transaction
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TRANSACTION_ID)]
        private string $transactionId = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATE)]
        private string $state = '',

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_LOGIN_ID)]
        #[OmitWhenEmpty]
        private ?int $loginId = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        #[OmitWhenEmpty]
        private ?string $hash = null,
    ) {}

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return ?int
     */
    public function getLoginId(): ?int
    {
        return $this->loginId;
    }

    /**
     * @return ?string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }
}
