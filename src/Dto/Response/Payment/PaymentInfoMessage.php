<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
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
 *
 * @api
 */
final readonly class PaymentInfoMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TRANSACTION_ID = 'transaction_id';
    public const string FIELD_STATE = 'state';
    /**-@#*/

    /**
     * @param string $transactionId
     * @param string $state NEW, REGISTERED, PENDING or DONE
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TRANSACTION_ID)]
        public string $transactionId = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATE)]
        public string $state = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
