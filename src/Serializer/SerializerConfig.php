<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Psr\Cache\CacheItemPoolInterface;

/**
 * {@inheritDoc}
 */
final readonly class SerializerConfig implements SerializerConfigInterface
{
    /**
     * Leaves UTF-8 as it is, does not escape forward slashes, and keeps a float a float.
     *
     * The first two are about what the payload looks like to someone reading a log. The third is
     * not cosmetic: without JSON_PRESERVE_ZERO_FRACTION a cash-on-delivery amount of 250.0 is
     * written as `250`, and any override that drops it changes what goes on the wire.
     */
    public const int DEFAULT_JSON_OPTIONS = \JSON_UNESCAPED_UNICODE
        | \JSON_UNESCAPED_SLASHES
        | \JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param CacheItemPoolInterface|null $metadataCache a pool to keep mapping metadata in
     * @param int $jsonEncodeOptions flags for `json_encode` on the request body
     * @param \DateTimeZone|null $timezone the zone dates are read into
     * @param bool $rejectUnknownFields whether an unrecognised response field is an error
     */
    public function __construct(
        /**
         * @var CacheItemPoolInterface|null
         */
        private ?CacheItemPoolInterface $metadataCache = null,

        /**
         * @var int
         */
        private int $jsonEncodeOptions = self::DEFAULT_JSON_OPTIONS,

        /**
         * @var \DateTimeZone|null
         */
        private ?\DateTimeZone $timezone = null,

        /**
         * @var bool
         */
        private bool $rejectUnknownFields = false,
    ) {}

    /**
     * @return CacheItemPoolInterface|null
     */
    public function getMetadataCache(): ?CacheItemPoolInterface
    {
        return $this->metadataCache;
    }

    /**
     * @return int
     */
    public function getJsonEncodeOptions(): int
    {
        return $this->jsonEncodeOptions;
    }

    /**
     * @return \DateTimeZone|null
     */
    public function getTimezone(): ?\DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * @return bool
     */
    public function rejectUnknownFields(): bool
    {
        return $this->rejectUnknownFields;
    }
}
