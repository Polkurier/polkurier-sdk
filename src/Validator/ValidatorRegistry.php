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
 * Dispatches a message to the validators that understand it.
 *
 * A validator registered for a class also applies to its subclasses and to anything implementing
 * it as an interface, so a rule written once for a base message covers the specialised ones —
 * registering against an order-number message validates every call that carries one.
 *
 * Being a validator itself means the service layer holds one collaborator rather than knowing
 * about a registry, and an application that wants entirely different rules can substitute its own.
 *
 * Failures are gathered across every validator that applies and reported together. Each one throws
 * on its own, so without this a payload wrong in two layers — a malformed field and an incoherent
 * combination — would report the first and hide the second until it was fixed.
 *
 * A message with no validator registered passes. The SDK validates what the API is known to check;
 * silence means "no rule", not "approved".
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
     * Runs every validator that applies, in registration order, and reports what they all found.
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
