<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Requires the amount for a prepaid top-up.
 *
 * Topping up the prepaid balance is the only mode where the caller sets the amount. For the other
 * modes the amount follows from what is being paid (outstanding invoices, unpaid orders). That is
 * why the rule lives here instead of on the field.
 *
 * An absent amount and a zero one are different faults and are reported as such: the first is
 * blank, the second is out of range. A negative one is out of range for the field whatever the
 * mode, so {@see \Polkurier\Webservice\Validator\Rule\NonNegative} on
 * {@see RegisterPaymentMessage::$amount} reports it and this validator stays silent, leaving one
 * violation per fault.
 *
 * The API's minimum top-up is not checked here.
 *
 * @extends AbstractMessageValidator<RegisterPaymentMessage>
 */
class RegisterPaymentMessageValidator extends AbstractMessageValidator
{
    /**
     * @param RegisterPaymentMessage $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        if ($data->mode !== PaymentModeEnum::Prepaid) {
            return;
        }

        if ($data->amount === null) {
            $violations->add(
                RegisterPaymentMessage::FIELD_AMOUNT,
                'is required when topping up the prepaid balance',
                ValidationErrorCodeEnum::Blank,
                [
                    'field' => RegisterPaymentMessage::FIELD_MODE,
                    'condition' => 'equals',
                    'equals' => PaymentModeEnum::Prepaid->value,
                ],
            );

            return;
        }

        if ($data->amount > 0.0) {
            return;
        }

        // A negative amount is already out of range for the field itself, whatever the mode, and
        // {@see \Polkurier\Webservice\Validator\Rule\NonNegative} on the property reports it.
        // Reporting it again here would put 2 violations on 1 field for 1 fault.
        if ($data->amount < 0.0) {
            return;
        }

        $violations->add(
            RegisterPaymentMessage::FIELD_AMOUNT,
            'must be greater than zero when topping up the prepaid balance',
            ValidationErrorCodeEnum::OutOfRange,
            ['minimum' => 0, 'exclusiveMinimum' => true],
        );
    }
}
