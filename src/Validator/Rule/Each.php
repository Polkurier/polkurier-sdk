<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Applies a rule to every entry of a list.
 *
 * Use it for lists of plain values, such as order numbers or waybill numbers, where each entry has
 * its own format. For lists of objects use {@see Valid}, so each object applies its own rules.
 *
 * Failures are reported with the index, e.g. `orderno[1]`.
 *
 * ```php
 * #[NotEmptyList]
 * #[Each(OrderNumber::class)]
 * private array $orderNo;
 * ```
 *
 * The rule is given as a class name and built without arguments. A rule that needs arguments, or
 * depends on another field, has to go in a message validator.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Each
{
    /**
     * @param class-string<ValueValidatorInterface> $rule the rule to apply to each entry
     *
     * @throws InvalidArgumentException when the named class is not a value rule
     */
    public function __construct(
        /**
         * @var class-string<ValueValidatorInterface>
         */
        public readonly string $rule,
    ) {
        if (!\is_subclass_of($rule, ValueValidatorInterface::class)) {
            throw new InvalidArgumentException(\sprintf(
                '%s is not a %s, so it cannot check list entries.',
                $rule,
                ValueValidatorInterface::class,
            ));
        }
    }

    /**
     * @return ValueValidatorInterface
     */
    public function rule(): ValueValidatorInterface
    {
        /** @var ValueValidatorInterface */
        return new ($this->rule)();
    }
}
