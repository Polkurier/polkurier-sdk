<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Names a validator that checks this message as a whole.
 *
 * Most rules fit on the fields, and most of the rest fit in a class-level rule such as
 * {@see \Polkurier\Webservice\Validator\Rule\RequiredWhen},
 * {@see \Polkurier\Webservice\Validator\Rule\InOrder} or
 * {@see \Polkurier\Webservice\Validator\Rule\IgnoredWhen}. A few rules need real logic: which
 * address fields are required depends on whether the address is the sender or the recipient, and
 * whether an amount is required depends on what the payment is for. Those rules are written as
 * classes, and this attribute tells the message which ones apply.
 *
 * Declaring it on the message keeps every rule for that message in one place. There is no
 * separate list to keep in sync, so a validator can't end up registered for the wrong message.
 *
 * Nested payloads keep their rules too. A message reached through
 * {@see \Polkurier\Webservice\Validator\Rule\Valid} runs whatever validators it declares.
 *
 * The named class is built without arguments, so any dependencies need default values.
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
