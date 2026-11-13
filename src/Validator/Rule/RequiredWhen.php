<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFields;
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
        /**
         * @var string
         */
        public readonly string $field,

        /**
         * @var string
         */
        public readonly string $property,

        /**
         * @var mixed
         */
        public readonly mixed $equals = true,

        /**
         * @var string|null
         */
        public readonly ?string $reason = null,
    ) {}

    /**
     * @param MessageFields $fields
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(MessageFields $fields, ViolationPool $violations): void
    {
        if ($fields->value($this->property) !== $this->equals) {
            return;
        }

        if ((new NotBlank())->validate($fields->value($this->field)) === []) {
            return;
        }

        $violations->add(
            $fields->field($this->field),
            $this->reason ?? $this->reason($fields->field($this->property)),
        );
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
    protected function reason(string $property): string
    {
        return match (true) {
            $this->equals === true => \sprintf('is required when %s is set', $property),
            $this->equals === false => \sprintf('is required when %s is not set', $property),
            $this->equals === null, $this->equals === [], $this->equals === '' =>
                \sprintf('is required when %s is empty', $property),
            \is_scalar($this->equals) => \sprintf('is required when %s is %s', $property, $this->equals),
            default => \sprintf('is required when %s has that value', $property),
        };
    }
}
