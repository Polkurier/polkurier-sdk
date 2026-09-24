<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Names a validator that checks this message as a whole.
 *
 * Most of what a payload has to satisfy fits on its fields, and the rest usually fits in one of
 * the class-level rules — {@see RequiredWhen}, {@see InOrder}, {@see IgnoredWhen}. What is left is
 * the handful of rules with real logic behind them: which address fields are required depends on
 * whether the address is a sender or a recipient, and whether an amount is required depends on
 * what the payment is for. Those are written as classes, and this is how a message says which ones
 * apply to it.
 *
 * Declaring it here rather than in a registry keeps the wiring in one place. A message carries
 * every rule it is subject to, whether that rule is a field attribute or a class, and the pairing
 * cannot drift: there is no second list to keep in step, and no way to register a validator
 * against the wrong message and have it quietly stop running.
 *
 * It also means a nested payload keeps its rules. A message reached through {@see Valid} is
 * validated by whatever it declares, which a registry keyed on the top-level payload could not do.
 *
 * The named class is constructed without arguments, so it must be constructible that way;
 * collaborators belong in default parameter values.
 *
 * ```php
 * #[ValidatedBy(CreateOrderMessageValidator::class)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ValidatedBy
{
    /**
     * @param class-string<ValidatorInterface> $validator
     *
     * @throws InvalidArgumentException when the named class is not a validator
     */
    public function __construct(
        /**
         * @var class-string<ValidatorInterface>
         */
        public readonly string $validator,
    ) {
        if (!\is_subclass_of($validator, ValidatorInterface::class)) {
            throw new InvalidArgumentException(\sprintf(
                '%s is not a %s, so it cannot validate a message.',
                $validator,
                ValidatorInterface::class,
            ));
        }
    }
}
