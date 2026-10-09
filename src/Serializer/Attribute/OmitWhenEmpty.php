<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * Leaves this field out of the request when it is empty.
 *
 * For optional fields, a missing field and a field sent empty mean different things. So an empty
 * optional field is removed from the payload instead of being sent as null.
 *
 * The serializer component can only skip nulls across the whole payload, so this is declared per
 * field and applied by {@see \Polkurier\Webservice\Serializer\OmitEmptyNormalizer}.
 *
 * Empty means null, the empty string, or the empty array. `false` and `0` are meaningful values,
 * so they are sent.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class OmitWhenEmpty
{
}
