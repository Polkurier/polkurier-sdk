<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A registered payment.
 *
 * The transaction id and the access token together open the payment page for the customer. The
 * transaction id is the only way to look up the payment later.
 */
readonly class RegisteredPaymentMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TRANSACTION_ID = 'transaction_id';
    public const string FIELD_STATE = 'state';
    public const string FIELD_ACCESS_TOKEN = 'access_token';
    /**-@#*/

    /**
     * @param string $transactionId identifier to quote when checking status
     * @param string $state NEW, REGISTERED, PENDING or DONE, as in
     *        {@see \Polkurier\Webservice\Response\Payment\PaymentInfoMessage}
     * @param ?string $accessToken opens the payment page, together with the transaction id
     * @param string|null $rawResponse the body of the response, as the API sent it
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

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
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
