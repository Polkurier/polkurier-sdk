<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;

/**
 * One way a response differed from what this version of the SDK expects, and was read anyway.
 *
 * @api
 */
readonly class ResponseDrift
{
    /**
     * @param ResponseDriftTypeEnum $type
     * @param string $path where in the response, by PHP property name, e.g. `[0].serviceCode`. An
     *        unknown field keeps the API's name, e.g. `[0].brand_new`
     * @param mixed $value the value as the API sent it, null for a missing field
     * @param string|null $expected the type the SDK expected: a PHP type, a class or an enum
     */
    public function __construct(
        /**
         * @var ResponseDriftTypeEnum
         */
        public ResponseDriftTypeEnum $type,

        /**
         * @var string
         */
        public string $path,

        /**
         * @var mixed
         */
        public mixed $value = null,

        /**
         * @var string|null
         */
        public ?string $expected = null,
    ) {}
}
