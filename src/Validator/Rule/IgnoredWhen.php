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
 * A block that does not apply once one of its fields says so.
 *
 * Some optional blocks are switched off by a value rather than by being left out: cash on delivery
 * with nothing to collect is the clearest case. An amount of zero means there is no collection, so
 * the fields beside it describe something that is not happening — they are not required and not
 * meaningful. Holding them to their usual rules would reject a payload that is perfectly valid.
 *
 * Declared on the class, since it governs the whole of it. When the condition holds, every other
 * rule the class declares — its own field rules and its cross-field rules alike — is skipped.
 *
 * ```php
 * #[IgnoredWhen(property: 'amount', equals: 0.0)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class IgnoredWhen
{
    /**
     * @param string $property the property that decides
     * @param mixed $equals the value of $property that makes the API disregard the block
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
