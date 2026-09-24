<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;
use Polkurier\Webservice\Exception\ValidationException;

/**
 * Collects the reasons a payload was rejected.
 *
 * Validators gather failures rather than throwing at the first one, so a caller fixing a payload
 * sees everything wrong with it at once instead of discovering the problems one round trip at a
 * time. That mirrors how the API reports its own validation, and the field names used here are the
 * wire names, which makes the two reports directly comparable.
 *
 * A pool and nothing more: it knows how to record a failure and how to run a
 * {@see ValueValidatorInterface} against a value, and holds no rules of its own. Validators are
 * handed one and drop their findings into it, which is what lets several of them — the attribute
 * rules, a hand-written message validator, a nested payload's own rules — contribute to a single
 * report.
 */
class ViolationPool
{
    /**
     * @var array<string, string[]>
     */
    protected array $errors = [];

    /**
     * Records a failure against a field.
     *
     * @param string $field the wire field name
     * @param string $message phrased to follow the field name, e.g. "must not be empty"
     *
     * @return void
     */
    public function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Runs value validators against one field, recording whatever they report.
     *
     * Every validator runs, so a value that is wrong in several ways is reported as such rather
     * than one reason at a time.
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
            foreach ($validator->validate($value) as $message) {
                $this->add($field, $message);
            }
        }

        return $this;
    }

    /**
     * Runs value validators only when a condition holds.
     *
     * The shape most cross-field rules take: a field that matters only because another field says
     * so — a bank account required because the money is going to one, a two-factor code required
     * because the number is being made the default.
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
        return $this->errors === [];
    }

    /**
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        return $this->errors;
    }

    /**
     * Merges another set in, prefixing its field names.
     *
     * Used when validating a nested structure so the reported field reads `sender.postcode` rather
     * than a bare `postcode` that could have come from either end of a shipment.
     *
     * @param string $prefix
     * @param self $other
     *
     * @return $this
     */
    public function mergeUnder(string $prefix, self $other): static
    {
        foreach ($other->toArray() as $field => $messages) {
            foreach ($messages as $singleMessage) {
                $this->add(\sprintf('%s.%s', $prefix, $field), $singleMessage);
            }
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
            throw new ValidationException($this->errors);
        }
    }
}
