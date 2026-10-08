<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * Writes this map's entries, or this object's fields, into the enclosing object instead of under
 * the field's own key.
 *
 * For payloads whose keys aren't known in advance, e.g. the answers to a contact form, whose field
 * names come from the form definition, and for a message sent with a few keys added, e.g. an order
 * priced for one carrier. An entry never replaces a key the object already writes. An inlined object
 * that is also marked {@see \Polkurier\Webservice\Validator\Rule\Valid} is validated at the
 * enclosing level, so its errors carry the field names as they are sent.
 *
 * Applied by {@see \Polkurier\Webservice\Serializer\OmitEmptyNormalizer}.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class InlineFields
{
}
