<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads an enum value as null when the enum doesn't have it, and records it.
 *
 * A value of another type than the enum's backing type is converted when it is a string or a
 * number, reads as null otherwise, and is recorded as {@see ResponseDriftTypeEnum::CastValue}. In a
 * text format such as CSV, a string that is exactly an integer is read silently for an
 * integer-backed enum.
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
        /** @var class-string<\BackedEnum> $type */
        $path = $context['deserialization_path'] ?? null;

        return static::read(
            $type,
            $data,
            \is_string($path) ? $path : '',
            ResponseDriftCollector::fromContext($context),
            $format === CsvEncoder::FORMAT,
        );
    }

    /**
     * The case for a value, or null when the enum doesn't have it.
     *
     * @param class-string<\BackedEnum> $enum
     * @param mixed $value
     * @param string $path
     * @param ResponseDriftCollector|null $drift receives the value when it is unknown or of another type
     * @param bool $text whether the value comes from a text format, which carries it as a string
     *
     * @return \BackedEnum|null
     */
    public static function read(
        string $enum,
        mixed $value,
        string $path,
        ?ResponseDriftCollector $drift,
        bool $text = false,
    ): ?\BackedEnum {
        $int = (new \ReflectionEnum($enum))->getBackingType()?->getName() === 'int';
        $exact = match (true) {
            $int && \is_int($value), !$int && \is_string($value) => $value,
            $int && $text && \is_string($value) => \filter_var($value, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE),
            default => null,
        };
        if ($exact !== null) {
            $case = $enum::tryFrom($exact);
            if ($case === null) {
                $drift?->add(ResponseDriftTypeEnum::UnknownEnumValue, $path, $value, $enum);
            }

            return $case;
        }

        $converted = match (true) {
            $int && \is_string($value) => \filter_var($value, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE),
            $int && \is_float($value) && $value == (int)$value => (int)$value,
            !$int && (\is_int($value) || \is_float($value)) => (string)$value,
            default => null,
        };
        $drift?->add(ResponseDriftTypeEnum::CastValue, $path, $value, $enum);

        return $converted === null ? null : $enum::tryFrom($converted);
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
        return ResponseDriftCollector::fromContext($context) !== null && \is_subclass_of($type, \BackedEnum::class);
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
