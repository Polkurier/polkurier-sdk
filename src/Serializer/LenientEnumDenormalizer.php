<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads an enum value as null when the enum doesn't have it, and records it.
 *
 * It only acts when the context holds an {@see \Polkurier\Webservice\Serializer\UnknownEnumValues}
 * collector, and runs before {@see \Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer}.
 * Without a collector it steps aside, and an unknown value fails as usual.
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
        $case = \is_int($data) || \is_string($data) ? $type::tryFrom($data) : null;
        if ($case === null) {
            $path = $context['deserialization_path'] ?? null;
            UnknownEnumValues::fromContext($context)?->add($type, $data, \is_string($path) ? $path : null);
        }

        return $case;
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
        return UnknownEnumValues::fromContext($context) !== null && \is_subclass_of($type, \BackedEnum::class);
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
