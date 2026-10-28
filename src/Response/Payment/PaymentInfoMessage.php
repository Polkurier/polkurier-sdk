<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The state of a payment.
 *
 * The state is one of:
 *
 * - `NEW`: registered, not yet passed to the payment provider.
 * - `REGISTERED`: the provider has the transaction, and the customer has not finished paying.
 * - `PENDING`: the customer is back from the provider, and the payment is not yet confirmed.
 * - `DONE`: paid.
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
     * @param string $state NEW, REGISTERED, PENDING or DONE
     * @param ?int $loginId not sent by the API, always null
     * @param ?string $hash not sent by the API, always null
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
         *
         * @deprecated The API does not send this field; it is always null.
         */
        #[Serializer\SerializedName(self::FIELD_LOGIN_ID)]
        #[OmitWhenEmpty]
        private ?int $loginId = null,

        /**
         * @var ?string
         *
         * @deprecated The API does not send this field; it is always null.
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
     *
     * @deprecated The API does not send this field; it is always null.
     */
    public function getLoginId(): ?int
    {
        return $this->loginId;
    }

    /**
     * @return ?string
     *
     * @deprecated The API does not send this field; it is always null.
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }
}
