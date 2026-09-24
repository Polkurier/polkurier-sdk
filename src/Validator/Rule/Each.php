<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;

/**
 * Apply a rule to every entry of a list rather than to the list itself.
 *
 * For lists of plain values — order numbers, waybill numbers — where each entry has a format of
 * its own. Lists of objects are handled by {@see Valid}, which lets each object declare its own
 * rules.
 *
 * Failures are reported against the index, so a bad entry reads as `orderno[1]` and can be found
 * without counting through the request.
 *
 * ```php
 * #[NotEmptyList]
 * #[Each(OrderNumber::class)]
 * private array $orderNo;
 * ```
 *
 * The rule is named by class and built with no arguments, which is all PHP attributes allow. A
 * parameterised rule, or one depending on a sibling field, needs a message validator instead.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Each
{
    /**
     * @param class-string<ValueValidatorInterface> $rule the rule to apply to each entry
     */
    public function __construct(
        /**
         * @var class-string<ValueValidatorInterface>
         */
        public readonly string $rule,
    ) {}

    /**
     * @return ValueValidatorInterface
     */
    public function rule(): ValueValidatorInterface
    {
        /** @var ValueValidatorInterface */
        return new ($this->rule)();
    }
}
