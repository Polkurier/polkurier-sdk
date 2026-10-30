<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;

/**
 * Collects how a response differed from what this version of the SDK expects.
 *
 * Put an instance in the denormalization context under {@see self::CONTEXT_KEY}. Responses are then
 * read leniently: unknown fields and enum values, missing fields and values of another type are
 * read as described by {@see ResponseDriftTypeEnum}, and each case is recorded here. Without it in
 * the context, they fail as usual.
 */
class ResponseDriftCollector
{
    public const string CONTEXT_KEY = 'polkurier_response_drift';

    /**
     * @var list<ResponseDrift>
     */
    private array $drift = [];

    /**
     * An empty string where an enum belongs isn't recorded. It reads as null like any other unknown
     * value.
     *
     * @param ResponseDriftTypeEnum $type
     * @param string $path
     * @param mixed $value
     * @param string|null $expected
     *
     * @return void
     */
    public function add(ResponseDriftTypeEnum $type, string $path, mixed $value = null, ?string $expected = null): void
    {
        if ($type === ResponseDriftTypeEnum::UnknownEnumValue && $value === '') {
            return;
        }

        $this->drift[] = new ResponseDrift($type, $path, $value, $expected);
    }

    /**
     * @return list<ResponseDrift>
     */
    public function all(): array
    {
        return $this->drift;
    }

    /**
     * The collector in the context, if there is one.
     *
     * @param array<string, mixed> $context
     *
     * @return self|null
     */
    public static function fromContext(array $context): ?self
    {
        $collector = $context[self::CONTEXT_KEY] ?? null;

        return $collector instanceof self ? $collector : null;
    }
}
