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
     *
     * Everything that has to find the bytes inside such a value reads it here: the denormalizer
     * that decodes the field, and the log writer, which sees the same value before any class is
     * built from it.
     */
    public const string MARKER = ';base64,';
}
