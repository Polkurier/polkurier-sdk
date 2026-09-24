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
 * The broad half of validation. Where a {@see \Polkurier\Webservice\Api\Validation\ValueValidatorInterface}
 * judges a single value against a format, a message validator judges a payload against itself: it
 * decides which fields are required, applies the value rules to them, and enforces the rules that
 * only make sense across several fields at once — a bank account required because the money is
 * going to one, a two-factor code required because a number is being made the default, a date
 * range that has to run forwards.
 *
 * Subclasses collect failures rather than throwing, so every problem with a payload is reported
 * together.
 *
 * Which messages a subclass applies to is not its own business and is not declared here: a message
 * names the validators that check it, with
 * {@see \Polkurier\Webservice\Validator\Rule\ValidatedBy}. Stating it on both sides invited
 * them to disagree, and a validator paired with the wrong message then stopped running without
 * saying so.
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
