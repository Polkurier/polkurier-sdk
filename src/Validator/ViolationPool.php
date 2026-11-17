<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Validator\Rule\ValueValidatorInterface;

/**
 * Collects the reasons a payload was rejected.
 *
 * Validators collect failures instead of throwing at the first one, so you see every problem with
 * a payload at once. The API reports its own validation errors the same way, and the field names
 * here are wire names, so the 2 reports can be compared directly.
 *
 * The pool holds no rules. It records failures and can run a {@see ValueValidatorInterface}
 * against a value. Several validators (attribute rules, a hand-written message validator, rules of
 * a nested payload) can write to the same pool and produce one report.
 */
class ViolationPool
{
    /**
     * @var list<Violation>
     */
    protected array $violations = [];

    /**
     * Records a failure against a field.
     *
     * @param string $field the wire field name
     * @param string $message phrased to follow the field name, e.g. "must not be empty"
     * @param ValidationErrorCodeEnum $code why the field was rejected. A rule written for one
     *        message leaves it, and the violation carries the fallback code
     * @param array<string, scalar> $parameters the values the message spells out, by name
     *
     * @return void
     */
    public function add(
        string $field,
        string $message,
        ValidationErrorCodeEnum $code = ValidationErrorCodeEnum::Other,
        array $parameters = [],
    ): void {
        $this->violations[] = new Violation($field, $code, $message, $parameters);
    }

    /**
     * Takes a failure another pool already located, keeping the code it was recorded with.
     *
     * A validator that reports by throwing hands its findings on this way, so passing them from
     * one pool to the next does not reduce them to their English.
     *
     * @param Violation $violation
     *
     * @return void
     */
    public function take(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    /**
     * Runs value validators against one field and records what they report.
     *
     * All validators run, so a value that is wrong in several ways gets all of its reasons.
     *
     * @param string $field the wire field name
     * @param mixed $value
     * @param ValueValidatorInterface ...$validators
     *
     * @return $this
     */
    public function check(string $field, mixed $value, ValueValidatorInterface ...$validators): static
    {
        foreach ($validators as $validator) {
            foreach ($validator->validate($value) as $failure) {
                $this->violations[] = new Violation(
                    $field,
                    $failure->code,
                    $failure->message,
                    $failure->parameters,
                );
            }
        }

        return $this;
    }

    /**
     * Runs value validators only if the condition is true.
     *
     * For a rule that applies only in some cases, e.g. an email address that the sender needs and
     * the recipient doesn't.
     *
     * @param bool $condition
     * @param string $field
     * @param mixed $value
     * @param ValueValidatorInterface ...$validators
     *
     * @return $this
     */
    public function checkWhen(
        bool $condition,
        string $field,
        mixed $value,
        ValueValidatorInterface ...$validators,
    ): static {
        if ($condition) {
            $this->check($field, $value, ...$validators);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->violations === [];
    }

    /**
     * Every failure, in the order it was recorded.
     *
     * @return list<Violation>
     */
    public function all(): array
    {
        return $this->violations;
    }

    /**
     * The failures as the English report, keyed by wire field path.
     *
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        $errors = [];
        foreach ($this->violations as $violation) {
            $errors[$violation->path][] = $violation->message;
        }

        return $errors;
    }

    /**
     * Merges another pool into this one, prefixing its field names.
     *
     * Used for nested structures, so the reported field is `sender.postcode` and not just
     * `postcode`.
     *
     * @param string $prefix
     * @param self $other
     *
     * @return $this
     */
    public function mergeUnder(string $prefix, self $other): static
    {
        foreach ($other->all() as $violation) {
            $this->violations[] = new Violation(
                \sprintf('%s.%s', $prefix, $violation->path),
                $violation->code,
                $violation->message,
                $violation->parameters,
            );
        }

        return $this;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function throwIfAny(): void
    {
        if (!$this->isEmpty()) {
            throw new ValidationException($this->toArray(), violations: $this->violations);
        }
    }
}
