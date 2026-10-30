<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads a list of enum values, leaving out and recording the values the enum doesn't know, as
 * {@see LenientEnumDenormalizer::read()} does for one value.
 *
 * It only acts when the context holds a
 * {@see \Polkurier\Webservice\Serializer\ResponseDriftCollector}, and runs before the array
 * normalizer, so the list only holds enum cases. Without a collector it steps aside, and an unknown
 * value fails as usual.
 *
 * A list can also arrive as a JSON object with numeric keys, e.g. `{"1": "ROD"}`. When every key is
 * numeric, the keys are dropped and the result is a plain list. Otherwise the keys are kept.
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
        /** @var class-string<\BackedEnum> $enum */
        $enum = \substr($type, 0, -2);
        /** @var array<array-key, mixed> $data */
        $path = $context['deserialization_path'] ?? null;
        $drift = ResponseDriftCollector::fromContext($context);
        $cases = [];
        foreach ($data as $key => $value) {
            $case = LenientEnumDenormalizer::read(
                $enum,
                $value,
                (\is_string($path) ? $path : '') . '[' . $key . ']',
                $drift,
                $format === CsvEncoder::FORMAT,
            );
            if ($case !== null) {
                $cases[$key] = $case;
            }
        }

        $numericKeys = \array_filter(\array_keys($data), \is_int(...)) === \array_keys($data);

        return $numericKeys ? \array_values($cases) : $cases;
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
        return ResponseDriftCollector::fromContext($context) !== null
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
