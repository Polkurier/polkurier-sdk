<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads a list of enum values, leaving out and recording the values the enum doesn't know, as
 * {@see LenientEnumDenormalizer::read()} does for one value. A value the list repeats is kept
 * once, under the first key it arrived under, since a case says what it says however often it is
 * sent.
 *
 * It only acts when the context holds a
 * {@see \Polkurier\Webservice\Serializer\ResponseDriftCollector}, and runs before the array
 * normalizer, so the list only holds enum cases. Without a collector it steps aside, and an unknown
 * value fails as usual.
 *
 * A list can also arrive as an object, e.g. `{"1": "ROD"}`. PHP reads a key that spells an integer
 * as an integer, so when every key is an integer the keys are dropped and the result is a plain
 * list. A key that spells a number any other way, e.g. `"01"`, stays a string, and the field holds
 * a map under the keys as they arrived.
 */
class EnumListDenormalizer implements DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return array<array-key, \BackedEnum>
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): array
    {
        $enum = \substr($type, 0, -2);
        $path = $context[ResponseDriftCollector::PATH_KEY] ?? null;
        $drift = ResponseDriftCollector::findInContext($context);
        $cases = [];
        $seen = [];
        foreach ($data as $key => $value) {
            $case = LenientEnumDenormalizer::read(
                $enum,
                $value,
                (\is_string($path) ? $path : '') . '[' . $key . ']',
                $drift,
            );
            if ($case === null || isset($seen[$case->value])) {
                continue;
            }
            $seen[$case->value] = true;
            $cases[$key] = $case;
        }

        $integerKeys = \array_filter(\array_keys($data), \is_int(...)) === \array_keys($data);

        return $integerKeys ? \array_values($cases) : $cases;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return bool
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool {
        return ResponseDriftCollector::findInContext($context) !== null
            && \is_array($data)
            && \str_ends_with($type, '[]')
            && \is_subclass_of(\substr($type, 0, -2), \BackedEnum::class);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
