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
     */
    case MissingField = 'MISSING_FIELD';

    /**
     * A field or a list item came with a value of another type, or a date that doesn't parse. A
     * simple value is converted. Anything else reads as the field's default or an empty value, and
     * a list item is left out.
     */
    case CastValue = 'CAST_VALUE';

    /**
     * An enum value the SDK doesn't know. It reads as null, or is left out of a list.
     */
    case UnknownEnumValue = 'UNKNOWN_ENUM_VALUE';
}
