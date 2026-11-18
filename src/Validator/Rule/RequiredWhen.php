<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFieldReader;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A field that is required when another field has a given value.
 *
 * The most common cross-field rule, e.g. a bank account that is required because the money goes to
 * one, or a two-factor code that is required when a number becomes the default. Goes on the class,
 * since it involves 2 fields.
 *
 * ```php
 * #[RequiredWhen(field: 'twoFactorCode', property: 'default', equals: true)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class RequiredWhen implements MessageRuleInterface
{
    /**
     * @param string $field the property that becomes mandatory
     * @param string $property the property to check
     * @param mixed $equals the value of $property that makes $field mandatory
     * @param string|null $reason the report's wording, in place of the default
     */
    public function __construct(
        public readonly string $field,

        public readonly string $property,

        public readonly mixed $equals = true,

        public readonly ?string $reason = null,
    ) {}

    /**
     * @param MessageFieldReader $fields
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(MessageFieldReader $fields, ViolationPool $violations): void
    {
        if ($fields->readValue($this->property) !== $this->equals) {
            return;
        }

        if ((new NotBlank())->validate($fields->readValue($this->field)) === []) {
            return;
        }

        $property = $fields->readField($this->property);
        $parameters = ['field' => $property, 'condition' => $this->getCondition()];
        $value = $this->getConditionValue();
        if ($value !== null) {
            $parameters['equals'] = $value;
        }

        $violations->add(
            $fields->readField($this->field),
            $this->reason ?? $this->buildReason($property),
            ValidationErrorCodeEnum::Blank,
            $parameters,
        );
    }

    /**
     * @return list<string>
     */
    public function listProperties(): array
    {
        return [$this->field, $this->property];
    }

    /**
     * The default wording, which states the condition the rule actually tests.
     *
     * The rule fires on equality with $equals, so a single wording would be wrong for every value
     * but `true` and leave each use to supply its own.
     *
     * @param string $property the wire name of the property being tested
     *
     * @return string
     */
    protected function buildReason(string $property): string
    {
        return match ($this->getCondition()) {
            'set' => \sprintf('is required when %s is set', $property),
            'not_set' => \sprintf('is required when %s is not set', $property),
            'empty' => \sprintf('is required when %s is empty', $property),
            default => $this->getConditionValue() === null
                ? \sprintf('is required when %s has that value', $property)
                : \sprintf('is required when %s is %s', $property, $this->getConditionValue()),
        };
    }

    /**
     * Which kind of condition makes the field required, as the violation reports it.
     *
     * The wording above is built from this, so a report and a translation of it describe the same
     * condition.
     *
     * @return string one of `set`, `not_set`, `empty` or `equals`
     */
    protected function getCondition(): string
    {
        return match (true) {
            $this->equals === true => 'set',
            $this->equals === false => 'not_set',
            $this->equals === null, $this->equals === [], $this->equals === '' => 'empty',
            default => 'equals',
        };
    }

    /**
     * The value the condition compares against, or null when it compares against no value.
     *
     * A backed enum is reported as its backing value, which is what the API carries and what a
     * caller reading the payload sees.
     *
     * @return scalar|null
     */
    protected function getConditionValue(): string|int|float|bool|null
    {
        if ($this->getCondition() !== 'equals') {
            return null;
        }
        if ($this->equals instanceof \BackedEnum) {
            return $this->equals->value;
        }

        return \is_scalar($this->equals) ? $this->equals : null;
    }
}
