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
 * A payment that has been registered and is waiting to be completed.
 *
 * The access token is for the payment provider's hosted flow, and is only needed until that flow
 * starts. The transaction id is the only way to look up the payment later.
 */
readonly class RegisteredPaymentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TRANSACTION_ID = 'transaction_id';
    public const string FIELD_STATE = 'state';
    public const string FIELD_ACCESS_TOKEN = 'access_token';
    /**-@#*/

    /**
     * @param string $transactionId identifier to quote when checking status
     * @param string $state where the payment has got to
     * @param ?string $accessToken token for the provider's hosted flow
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
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_ACCESS_TOKEN)]
        #[OmitWhenEmpty]
        private ?string $accessToken = null,
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
     * @return ?string
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}
