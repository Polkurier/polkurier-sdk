<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads an empty string where a date was expected.
 *
 * An optional date can arrive as `""` rather than as null or absent. The date normalizer refuses
 * that, correctly — an empty string is not a date — and the call fails with a message about parse
 * formats that says nothing about which field or why.
 *
 * So this sits in front of it and answers the empty string with null. It claims nothing else: a
 * real date string falls through to {@see DateTimeNormalizer} untouched, and whether null is
 * acceptable is then the receiving property's business, which is where that decision belongs.
 */
final class AbsentDateNormalizer implements DenormalizerInterface
{
    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return null
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return null;
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
        return $data === '' && \is_a($type, \DateTimeInterface::class, true);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            \DateTimeInterface::class => false,
            \DateTimeImmutable::class => false,
            \DateTime::class => false,
        ];
    }
}
