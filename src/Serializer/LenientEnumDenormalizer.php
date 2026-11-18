<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads an enum value as null when the enum doesn't have it, and records it.
 *
 * Every enum the SDK declares is backed by strings. A value of another type is converted when it is
 * a number, reads as null otherwise, and is recorded as {@see ResponseDriftTypeEnum::CastValue}.
 *
 * It only acts when the context holds a
 * {@see \Polkurier\Webservice\Serializer\ResponseDriftCollector}, and runs before
 * {@see \Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer}. Without a collector it
 * steps aside, and an unknown value fails as usual.
 */
class LenientEnumDenormalizer implements DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return \BackedEnum|null
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ?\BackedEnum
    {
        $path = $context[ResponseDriftCollector::PATH_KEY] ?? null;

        return static::read(
            $type,
            $data,
            \is_string($path) ? $path : '',
            ResponseDriftCollector::findInContext($context),
        );
    }

    /**
     * The case for a value, or null when the enum doesn't have it.
     *
     * @param class-string<\BackedEnum> $enum a string-backed enum
     * @param mixed $value
     * @param string $path
     * @param ResponseDriftCollector|null $drift receives the value when it is unknown or of another type
     *
     * @return \BackedEnum|null
     */
    public static function read(
        string $enum,
        mixed $value,
        string $path,
        ?ResponseDriftCollector $drift,
    ): ?\BackedEnum {
        if (\is_string($value)) {
            $case = $enum::tryFrom($value);
            if ($case === null) {
                $drift?->add(ResponseDriftTypeEnum::UnknownEnumValue, $path, $value, $enum);
            }

            return $case;
        }

        $drift?->add(ResponseDriftTypeEnum::CastValue, $path, $value, $enum);

        return \is_int($value) || \is_float($value) ? $enum::tryFrom((string)$value) : null;
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
        return ResponseDriftCollector::findInContext($context) !== null && \is_subclass_of($type, \BackedEnum::class);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [\BackedEnum::class => false];
    }
}
