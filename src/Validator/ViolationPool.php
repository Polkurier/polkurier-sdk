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
            foreach ($validator->validate($value) as $message) {
                $this->add($field, $message);
            }
        }

        return $this;
    }

    /**
     * Runs value validators only if the condition is true.
     *
     * Most cross-field rules look like this, e.g. a bank account that is required because the
     * money goes to one, or a two-factor code that is required when the number becomes the default.
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
