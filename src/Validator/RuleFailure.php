<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;

/**
 * One reason a rule rejected a value, before anything says which field held it.
 *
 * A value rule checks a value in isolation, so it knows why the value is wrong and nothing about
 * where it came from. {@see ViolationPool::check()} turns each of these into a {@see Violation}
 * by naming the field.
 *
 * @api
 */
final readonly class RuleFailure
{
    /**
     * @param ValidationErrorCodeEnum $code why the value was rejected
     * @param string $message the reason in English, worded to follow the field name
     * @param array<string, scalar> $parameters the values the message spells out, by name, for an
     *        integration wording it differently
     */
    public function __construct(
        public ValidationErrorCodeEnum $code,

        public string $message,

        /**
         * @var array<string, scalar>
         */
        public array $parameters = [],
    ) {}
}
