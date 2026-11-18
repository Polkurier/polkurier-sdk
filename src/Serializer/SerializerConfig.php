<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Api\SerializerConfigInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * How the SDK reads responses and writes payloads: strictly or leniently, with which metadata
 * cache, in which timezone, and with which JSON flags.
 *
 * The mapping itself (field names, date formats, which fields are dropped when empty) is fixed by
 * the API and declared in attributes on the classes. These are the settings that depend on your
 * application, and that often differ between a long-running worker and a CLI script, or between
 * CI and production. Construct this with the arguments you want, or implement
 * {@see SerializerConfigInterface} to read them from wherever your application keeps them, and
 * pass the result to {@see \Polkurier\Webservice\PolkurierWebservice}.
 *
 * It is the whole of what an application sets about serialization: the serializer it configures is
 * the SDK's own, see {@see \Polkurier\Webservice\Api\SerializerInterface}.
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
