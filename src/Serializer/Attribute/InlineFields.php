<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * Writes this map's entries into the enclosing object instead of under the field's own key.
 *
 * For payloads whose keys aren't known in advance, e.g. the answers to a contact form, whose field
 * names come from the form definition. An entry never replaces a key the object already writes.
 *
 * Applied by {@see \Polkurier\Webservice\Serializer\OmitEmptyNormalizer}.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class InlineFields
{
}
