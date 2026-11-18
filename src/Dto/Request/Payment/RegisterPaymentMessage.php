<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Payment;

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
 *
 * Invoice and order payments need their list of numbers; the SDK checks it. Other modes ignore both
 * lists.
 *
 * @api
 */
#[Validate\ValidatedBy(RegisterPaymentMessageValidator::class)]
#[Validate\RequiredWhen(
    field: 'invoiceNumbers',
    property: 'mode',
    equals: PaymentModeEnum::Invoices,
    reason: 'is required when paying invoices',
)]
#[Validate\RequiredWhen(
    field: 'orderNumbers',
    property: 'mode',
    equals: PaymentModeEnum::Orders,
    reason: 'is required when paying for orders',
)]
final readonly class RegisterPaymentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MODE = 'mode';
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_INVOICES = 'invoices';
    public const string FIELD_ORDERS = 'orders';
    /**-@#*/

    /**
     * @param PaymentModeEnum $mode what the payment settles
     * @param float|null $amount amount in PLN, where the mode allows a free choice
     * @param string[] $invoiceNumbers numbers of the invoices to pay, for {@see PaymentModeEnum::Invoices}
     * @param string[] $orderNumbers numbers of the orders to pay for, for {@see PaymentModeEnum::Orders}
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_MODE)]
        public PaymentModeEnum $mode,

        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        #[OmitWhenEmpty]
        #[Validate\NonNegative]
        public ?float $amount = null,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_INVOICES)]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\NotBlank::class)]
        public array $invoiceNumbers = [],

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\OrderNumber::class)]
        public array $orderNumbers = [],
    ) {}
}
