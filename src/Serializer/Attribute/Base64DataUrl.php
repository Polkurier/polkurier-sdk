<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * The API sends this field as a base64 `data:` URL. The property holds the decoded bytes.
 *
 * Applied by {@see \Polkurier\Webservice\Serializer\WireFormatDenormalizer}.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Base64DataUrl
{
    /**
     * What separates the URL's media type from the base64 it introduces.
     */
    public const string MARKER = ';base64,';

    /**
     * The base64 part of such a value, or null when it holds no marker.
     *
     * Everything that has to find the bytes inside one splits it here: the denormalizer that
     * decodes the field, and the log writer, which sees the value before any class is built from
     * it. They answer a value without a marker differently, so the split reports its absence
     * instead of deciding it.
     *
     * @param string $value
     *
     * @return string|null
     */
    public static function findEncoded(string $value): ?string
    {
        $marker = \strpos($value, self::MARKER);

        return $marker === false ? null : \substr($value, $marker + \strlen(self::MARKER));
    }
}
