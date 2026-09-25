<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Exception\ValidationException;

/**
 * Base for a validator that checks one whole message.
 *
 * A {@see \Polkurier\Webservice\Api\Validation\ValueValidatorInterface} checks a single value
 * against a format. A message validator checks the payload as a whole. It decides which fields are
 * required, applies the value rules to them, and checks rules that span several fields, e.g. a bank
 * account that is required because the money goes to one, a two-factor code that is required when
 * a number becomes the default, or a date range that must run forwards.
 *
 * Subclasses add failures to the pool instead of throwing, so all problems are reported together.
 *
 * A subclass doesn't declare which messages it applies to. The message names its validators with
 * {@see \Polkurier\Webservice\Validator\Rule\ValidatedBy}. Declaring the link in one place
 * means it can't get out of sync and silently stop a validator from running.
 *
 * @template T of MessageInterface
 */
abstract class AbstractMessageValidator implements ValidatorInterface
{
    /**
     * @param MessageInterface $data
     *
     * @return void
     * @throws ValidationException
     */
    public function validate(MessageInterface $data): void
    {
        $violations = new ViolationPool();
        /** @var T $data */
        $this->collect($data, $violations);
        $violations->throwIfAny();
    }

    /**
     * Gathers everything wrong with the message.
     *
     * @param T $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    abstract protected function collect(MessageInterface $data, ViolationPool $violations): void;
}
