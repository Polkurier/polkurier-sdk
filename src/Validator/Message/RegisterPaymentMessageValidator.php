<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * The amount is only meaningful for one of the payment modes, and required for that one.
 *
 * Topping up the prepaid balance is the only mode where the caller says how much: for the others
 * the amount is already determined by what is being settled — outstanding invoices, unpaid orders.
 * So the field cannot simply be required, and cannot simply be optional either, which is what puts
 * the rule here rather than on the field.
 *
 * Zero counts as missing: a top-up of nothing is not a top-up, and is refused with the same message
 * as an absent amount. The field rule alone would let a zero past, because zero is a value and
 * NotBlank has no opinion about it.
 *
 * What is not checked is the minimum top-up. There is one, but it is an account setting the caller
 * cannot see from here, so the service is left to enforce it.
 *
 * @extends AbstractMessageValidator<RegisterPaymentMessage>
 */
class RegisterPaymentMessageValidator extends AbstractMessageValidator
{
    /**
     * @param MessageInterface $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        /** @var RegisterPaymentMessage $data */
        if ($data->getMode() !== PaymentModeEnum::Prepaid) {
            return;
        }

        if (($data->getAmount() ?? 0.0) > 0.0) {
            return;
        }

        $violations->add(
            RegisterPaymentMessage::FIELD_AMOUNT,
            'is required when topping up the prepaid balance, and must be greater than zero',
        );
    }
}
