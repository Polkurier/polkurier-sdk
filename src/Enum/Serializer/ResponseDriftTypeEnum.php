<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Serializer;

/**
 * How a response differed from what the SDK expects.
 *
 * @api
 */
enum ResponseDriftTypeEnum: string
{
    /**
     * A field the SDK doesn't know. It is ignored.
     */
    case UnknownField = 'UNKNOWN_FIELD';

    /**
     * A field without a default was missing, or a field that can't be null was null. It reads as the
     * field's default, or as an empty value of its type: `''`, `0`, `0.0`, `false`, `[]` or null.
     *
     * A field that can't be null and has no empty value of its type, such as an object or an enum,
     * has nothing to fall back on: the difference is recorded and the read then fails on that field.
     */
    case MissingField = 'MISSING_FIELD';

    /**
     * A field or a list item came with a value of another type, or a date that doesn't parse. A
     * simple value is converted. Anything else reads as the field's default or an empty value, and
     * a list item is left out. Where the field has no empty value of its type, the value is
     * recorded as it arrived and the read fails on that field.
     */
    case CastValue = 'CAST_VALUE';

    /**
     * An enum value the SDK doesn't know. It reads as null, or is left out of a list.
     */
    case UnknownEnumValue = 'UNKNOWN_ENUM_VALUE';
}
