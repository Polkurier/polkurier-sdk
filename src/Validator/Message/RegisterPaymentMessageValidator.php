<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Requires the amount for a prepaid top-up.
 *
 * Topping up the prepaid balance is the only mode where the caller sets the amount. For the other
 * modes the amount follows from what is being paid (outstanding invoices, unpaid orders). That is
 * why the rule lives here instead of on the field.
 *
 * Zero counts as missing and gets the same message as an absent amount.
 * {@see \Polkurier\Webservice\Validator\Rule\NotBlank} alone would accept zero.
 *
 * The API's minimum top-up is not checked here.
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
