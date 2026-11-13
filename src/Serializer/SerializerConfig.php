<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Psr\Cache\CacheItemPoolInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
readonly class SerializerConfig implements SerializerConfigInterface
{
    /**
     * Doesn't escape UTF-8 or forward slashes, and keeps floats as floats.
     *
     * The first 2 only make the payload easier to read in logs. The third changes the payload:
     * without JSON_PRESERVE_ZERO_FRACTION a cash-on-delivery amount of 250.0 is written as `250`.
     */
    public const int DEFAULT_JSON_OPTIONS = \JSON_UNESCAPED_UNICODE
        | \JSON_UNESCAPED_SLASHES
        | \JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param CacheItemPoolInterface|null $metadataCache a pool to keep mapping metadata in
     * @param int $jsonEncodeOptions flags for `json_encode` on the request body
     * @param \DateTimeZone|null $timezone the zone a date without an offset is read in
     * @param bool $strictResponses whether a response that differs from the SDK is an error
     */
    public function __construct(
        /**
         * @var CacheItemPoolInterface|null
         */
        protected ?CacheItemPoolInterface $metadataCache = null,

        /**
         * @var int
         */
        protected int $jsonEncodeOptions = self::DEFAULT_JSON_OPTIONS,

        /**
         * @var \DateTimeZone|null
         */
        protected ?\DateTimeZone $timezone = null,

        /**
         * @var bool
         */
        protected bool $strictResponses = false,
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
    public function strictResponses(): bool
    {
        return $this->strictResponses;
    }
}
