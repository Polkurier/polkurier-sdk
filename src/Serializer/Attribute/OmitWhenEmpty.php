<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * Leave this field out of the request when it has nothing in it.
 *
 * A field that is not sent is not the same as a field sent empty: for the optional fields the two
 * mean different things, so an optional field with nothing in it has to disappear from the payload
 * rather than serialize as null.
 *
 * Nothing in the serializer component does this on its own — its own skipping is all-or-nothing
 * for null across the whole payload — so it is declared per field here and applied by
 * {@see \Polkurier\Webservice\Serializer\OmitEmptyNormalizer}.
 *
 * Empty means null, the empty string, or the empty array. `false` and `0` are meaningful values,
 * so they are sent.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class OmitWhenEmpty
{
}
