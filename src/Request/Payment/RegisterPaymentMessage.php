<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Message\RegisterPaymentMessageValidator;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Starts an online payment.
 *
 * The mode says what is being paid for. Send an amount only when topping up the prepaid balance,
 * where it is required (the SDK checks this before sending). For other modes the sum is set by
 * what is owed.
 */
#[Validate\ValidatedBy(RegisterPaymentMessageValidator::class)]
readonly class RegisterPaymentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MODE = 'mode';
    public const string FIELD_AMOUNT = 'amount';
    /**-@#*/

    /**
     * @param PaymentModeEnum $mode what the payment settles
     * @param ?float $amount amount in PLN, where the mode allows a free choice
     */
    public function __construct(
        /**
         * @var PaymentModeEnum
         */
        #[Serializer\SerializedName(self::FIELD_MODE)]
        private PaymentModeEnum $mode,

        /**
         * @var ?float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        #[Validate\NonNegative]
        #[OmitWhenEmpty]
        private ?float $amount = null,
    ) {}

    /**
     * @return PaymentModeEnum
     */
    public function getMode(): PaymentModeEnum
    {
        return $this->mode;
    }

    /**
     * @return ?float
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }
}
