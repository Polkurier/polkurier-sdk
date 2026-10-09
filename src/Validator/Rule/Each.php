<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Applies a rule to every entry of a list.
 *
 * For lists of plain values, such as order numbers or waybill numbers, where each entry has its own
 * format. A list of objects takes {@see \Polkurier\Webservice\Validator\Rule\Valid} instead, so
 * each object applies its own rules.
 *
 * Failures are reported with the index, e.g. `orderno[1]`. Arguments after the rule class go to
 * its constructor, e.g. `#[Each(OfType::class, PackMessage::class)]`.
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
     * @var array<array-key, mixed>
     */
    public readonly array $arguments;

    /**
     * @param class-string<ValueValidatorInterface> $rule the rule to apply to each entry
     * @param mixed ...$arguments passed to the rule's constructor
     *
     * @throws InvalidArgumentException when the named class is not a value rule
     */
    public function __construct(
        /**
         * @var class-string<ValueValidatorInterface>
         */
        public readonly string $rule,
        mixed ...$arguments,
    ) {
        $this->arguments = $arguments;

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
        return new ($this->rule)(...$this->arguments);
    }
}
