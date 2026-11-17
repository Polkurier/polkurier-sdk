<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Reads a date from the fields its format has, and nothing else.
 *
 * PHP fills the fields a format leaves out from the moment of reading: a `Y-m-d` date gets the
 * current time of day, and an `H:i` time gets today's date and the current seconds. So the same
 * value read twice gives two different dates. This reads it with `!` in front of the format, which
 * sets the fields it leaves out to zero: a date at midnight, a time on 1 January 1970.
 *
 * Writing is unchanged. Both directions go to {@see DateTimeNormalizer}.
 */
class ExactDateTimeDenormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param DateTimeNormalizer $inner
     * @param \DateTimeZone|null $timezone the zone a date without an offset is read in
     */
    public function __construct(
        protected readonly DateTimeNormalizer $inner = new DateTimeNormalizer(),
        protected readonly ?\DateTimeZone $timezone = null,
    ) {}

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return string
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        return $this->inner->normalize($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return bool
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return \DateTimeInterface
     */
    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): \DateTimeInterface {
        // Reading only. In the normalizer's own context the zone would also apply to writing,
        // where it would move a date the caller gave in another zone, and the fields a format
        // leaves out would move with it.
        if ($this->timezone !== null && !\array_key_exists(DateTimeNormalizer::TIMEZONE_KEY, $context)) {
            $context[DateTimeNormalizer::TIMEZONE_KEY] = $this->timezone;
        }

        $dateFormat = $context[DateTimeNormalizer::FORMAT_KEY] ?? null;

        // A Unix timestamp has every field already, and the inner normalizer reads it by its exact
        // format, so it is left as it is.
        if (\is_string($dateFormat) && !\str_contains($dateFormat, '!') && !\str_contains($dateFormat, '|')
            && !\str_contains($dateFormat, 'U')
        ) {
            $context[DateTimeNormalizer::FORMAT_KEY] = '!' . $dateFormat;
        }

        return $this->inner->denormalize($data, $type, $format, $context);
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
        return $this->inner->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
