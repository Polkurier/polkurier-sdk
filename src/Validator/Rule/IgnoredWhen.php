<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFields;

/**
 * Skips a block's rules when one of its fields has a given value.
 *
 * Some optional blocks are turned off by a value instead of being left out. The main example is
 * cash on delivery with an amount of zero: there is nothing to collect, so the other fields in the
 * block are not required. Applying their usual rules would reject a valid payload.
 *
 * Goes on the class. When the condition is met, all other rules the class declares (field rules
 * and cross-field rules) are skipped.
 *
 * ```php
 * #[IgnoredWhen(property: 'amount', equals: 0.0)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class IgnoredWhen
{
    /**
     * @param string $property the property to check
     * @param mixed $equals the value of $property that turns the block off
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $property,

        /**
         * @var mixed
         */
        public readonly mixed $equals = null,
    ) {}

    /**
     * @param MessageFields $fields
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function applies(MessageFields $fields): bool
    {
        return $fields->value($this->property) === $this->equals;
    }
}
