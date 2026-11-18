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
 * Two fields that must be in order.
 *
 * A reversed range matches nothing, so this rule rejects it. Works for dates and numbers.
 *
 * If either end is missing, the rule passes.
 *
 * ```php
 * #[InOrder(earlier: 'dateFrom', later: 'dateTo')]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class InOrder implements MessageRuleInterface
{
    /**
     * @param string $earlier the property that must not be greater than $later
     * @param string $later
     * @param string|null $reason the report's wording, in place of the default
     */
    public function __construct(
        public readonly string $earlier,

        public readonly string $later,

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
        $earlier = $fields->readValue($this->earlier);
        $later = $fields->readValue($this->later);

        if ($earlier === null || $later === null) {
            return;
        }
        if (!\is_scalar($earlier) && !($earlier instanceof \DateTimeInterface)) {
            return;
        }
        // Both sides, or the comparison is between kinds PHP orders by rules that say nothing
        // about the two values: an object always compares greater than a string.
        if (!\is_scalar($later) && !($later instanceof \DateTimeInterface)) {
            return;
        }
        if ($earlier <= $later) {
            return;
        }

        $laterField = $fields->readField($this->later);

        $violations->add(
            $fields->readField($this->earlier),
            $this->reason ?? \sprintf('must not be greater than %s', $laterField),
            ValidationErrorCodeEnum::OutOfOrder,
            ['field' => $laterField],
        );
    }

    /**
     * @return list<string>
     */
    public function listProperties(): array
    {
        return [$this->earlier, $this->later];
    }
}
