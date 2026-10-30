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
        private ResponseDriftTypeEnum $type,

        /**
         * @var string
         */
        private string $path,

        /**
         * @var mixed
         */
        private mixed $value = null,

        /**
         * @var string|null
         */
        private ?string $expected = null,
    ) {}

    /**
     * @return ResponseDriftTypeEnum
     */
    public function getType(): ResponseDriftTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return string|null null for an unknown field
     */
    public function getExpected(): ?string
    {
        return $this->expected;
    }
}
