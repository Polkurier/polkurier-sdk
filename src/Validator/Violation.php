<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;

/**
 * One reason a payload was rejected, and where.
 *
 * {@see \Polkurier\Webservice\Exception\ValidationException::getViolations()} hands these out
 * beside the English report. Match on {@see self::$code}, which does not change, and take the
 * numbers and names the sentence spells out from {@see self::$parameters}; the wording itself is
 * not contract.
 *
 * @api
 */
final readonly class Violation
{
    /**
     * @param string $path the wire field path, e.g. `sender.postcode` or `packs[1].weight`
     * @param ValidationErrorCodeEnum $code why the field was rejected
     * @param string $message the reason in English, worded to follow the field name
     * @param array<string, scalar> $parameters the values the message spells out, by name
     */
    public function __construct(
        /**
         * @var string
         */
        public string $path,

        /**
         * @var ValidationErrorCodeEnum
         */
        public ValidationErrorCodeEnum $code,

        /**
         * @var string
         */
        public string $message,

        /**
         * @var array<string, scalar>
         */
        public array $parameters = [],
    ) {}
}
