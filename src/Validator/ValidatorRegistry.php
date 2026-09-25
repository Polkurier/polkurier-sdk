<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\ValidationException;

/**
 * Passes a message to the validators registered for it.
 *
 * A validator registered for a class also runs for its subclasses, and for classes implementing it
 * if it is an interface. For example, a validator registered for an order-number message runs for
 * every call that carries one.
 *
 * The registry is a validator itself, so the services only depend on {@see ValidatorInterface}.
 * You can pass a completely different implementation instead.
 *
 * Failures from all matching validators are collected and thrown together in one exception. Each
 * validator throws on its own, so otherwise a payload with a malformed field and an invalid field
 * combination would only report the first problem.
 *
 * A message with no registered validator passes. That only means no rule applies to it.
 */
class ValidatorRegistry implements ValidatorInterface
{
    /**
     * @var array<class-string, ValidatorInterface[]>
     */
    protected array $validators = [];

    /**
     * Adds a validator for one message class.
     *
     * @param class-string $messageClass
     * @param ValidatorInterface $validator
     *
     * @return $this
     */
    public function register(string $messageClass, ValidatorInterface $validator): static
    {
        $this->validators[$messageClass][] = $validator;

        return $this;
    }

    /**
     * Runs every matching validator in registration order and reports all failures together.
     *
     * @param MessageInterface $data
     *
     * @return void
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function validate(MessageInterface $data): void
    {
        $violations = new ViolationPool();

        foreach ($this->validatorsFor($data) as $validator) {
            try {
                $validator->validate($data);
            } catch (ValidationException $e) {
                foreach ($e->getValidationErrors() as $field => $messages) {
                    foreach ($messages as $singleMessage) {
                        $violations->add($field, $singleMessage);
                    }
                }
            }
        }

        $violations->throwIfAny();
    }

    /**
     * @param MessageInterface $data
     *
     * @return ValidatorInterface[]
     */
    protected function validatorsFor(MessageInterface $data): array
    {
        $applicable = [];

        foreach ($this->validators as $messageClass => $validators) {
            if ($data instanceof $messageClass) {
                foreach ($validators as $validator) {
                    $applicable[] = $validator;
                }
            }
        }

        return $applicable;
    }
}
