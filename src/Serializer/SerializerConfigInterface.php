<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Serializer settings that vary between applications.
 *
 * The mapping itself (field names, date formats, which fields are dropped when empty) is fixed by
 * the API and declared in attributes on the classes. This interface covers the settings that
 * depend on your application, and that often differ between a long-running worker and a CLI
 * script, or between CI and production.
 *
 * Implement it, or construct {@see \Polkurier\Webservice\Serializer\SerializerConfig} with the
 * arguments you want, and pass the result to
 * {@see \Polkurier\Webservice\Serializer\SerializerProvider}.
 */
interface SerializerConfigInterface
{
    /**
     * A PSR-6 pool to cache the mapping metadata in, or null to rebuild it each time.
     *
     * Reading the attributes of around 150 classes is the slowest part of start-up, and the result
     * doesn't change until the code does. Pass a pool for a long-running worker or a warmed web
     * process. A short CLI script that makes one call gains nothing from it, and while editing the
     * classes you want the metadata rebuilt every time.
     *
     * @return CacheItemPoolInterface|null
     */
    public function getMetadataCache(): ?CacheItemPoolInterface;

    /**
     * Flags for `json_encode` when a request body is written.
     *
     * The default doesn't escape UTF-8. Most payloads contain Polish place names, so `Poznań` stays
     * as it is instead of becoming `Pozna\u0144`. That is shorter and readable in a log. Both forms
     * are valid JSON and the API accepts either.
     *
     * `JSON_PRETTY_PRINT` helps while debugging. The value replaces
     * {@see \Polkurier\Webservice\Serializer\SerializerConfig::DEFAULT_JSON_OPTIONS}, which
     * includes `JSON_PRESERVE_ZERO_FRACTION`, without which an amount of 250.0 is written as `250`.
     * `JSON_NUMERIC_CHECK` would turn a postcode into a number.
     *
     * @return int
     */
    public function getJsonEncodeOptions(): int;

    /**
     * The timezone to convert dates into, or null to keep the timezone the API sent.
     *
     * The API answers in Europe/Warsaw. If your application works in UTC, or shows times to
     * customers in other zones, set this to convert every date as it is read.
     *
     * @return \DateTimeZone|null
     */
    public function getTimezone(): ?\DateTimeZone;

    /**
     * Whether an unknown field in the response is an error.
     *
     * Off by default, because the API can add fields at any time, and a new field would otherwise
     * break a working integration.
     *
     * Turn it on in CI against a staging API to find out when the response gets a new field, so
     * the SDK can be updated.
     *
     * @return bool
     */
    public function rejectUnknownFields(): bool;
}
