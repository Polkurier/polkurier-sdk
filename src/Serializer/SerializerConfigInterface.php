<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Psr\Cache\CacheItemPoolInterface;

/**
 * The serializer settings an integration is likely to want different from the next one's.
 *
 * Everything the SDK maps — which field is called what, which format a date is in, which fields
 * disappear when empty — is fixed by the API and lives in attributes on the classes. None of that
 * is here. What is here is the handful of decisions that belong to the application rather than to
 * the protocol, and that tend to differ between a long-running worker and a CLI script, or
 * between a CI run and production.
 *
 * Implement it, or construct {@see SerializerConfig} with the arguments you want, and pass the
 * result to {@see SerializerProvider}.
 */
interface SerializerConfigInterface
{
    /**
     * A PSR-6 pool to keep the mapping metadata in, or null to rebuild it each time.
     *
     * Reading the attributes off around a hundred and fifty classes is the most expensive thing
     * the SDK does at start-up, and the result never changes between deployments of the same
     * code. A long-running worker or a warmed web process should hand over a pool and pay that
     * cost once; a short CLI script that makes one call will not get the cost back, and a
     * developer editing the classes wants the metadata rebuilt every time.
     *
     * @return CacheItemPoolInterface|null
     */
    public function getMetadataCache(): ?CacheItemPoolInterface;

    /**
     * Flags for `json_encode` when a request body is written.
     *
     * The default leaves UTF-8 alone rather than escaping it, which matters here because most
     * payloads carry Polish place names: `Poznań` stays itself instead of becoming `Poznań`,
     * which is shorter on the wire and legible in a log. Both forms are valid JSON and the API
     * reads either.
     *
     * Add `JSON_PRETTY_PRINT` while debugging. Build on
     * {@see SerializerConfig::DEFAULT_JSON_OPTIONS} rather than replacing it: it carries
     * `JSON_PRESERVE_ZERO_FRACTION`, without which an amount of 250.0 is written as `250`. And do
     * not add `JSON_NUMERIC_CHECK`, which would turn a postcode into a number.
     *
     * @return int
     */
    public function getJsonEncodeOptions(): int;

    /**
     * The timezone dates are read into, or null to keep whatever the API sent.
     *
     * The API answers in Europe/Warsaw. An application that works in UTC internally, or that
     * shows times to customers elsewhere, can have every date converted on the way in rather than
     * remembering to do it at each use.
     *
     * @return \DateTimeZone|null
     */
    public function getTimezone(): ?\DateTimeZone;

    /**
     * Whether a field in the response that the SDK does not know about is an error.
     *
     * Off by default, and that is the right setting in production: the API gains fields without
     * warning, and a new one arriving should not break a working integration.
     *
     * On is useful in CI against a staging API, where the point is to be told that the response
     * has grown a field so the SDK can be updated deliberately, rather than finding out when
     * someone notices the data was never being read.
     *
     * @return bool
     */
    public function rejectUnknownFields(): bool;
}
