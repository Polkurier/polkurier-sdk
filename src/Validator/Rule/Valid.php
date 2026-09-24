<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * Descend into this property and apply whatever rules the nested value declares.
 *
 * An object is checked in place; a list has every entry checked. Reported fields carry the path,
 * so a bad parcel reads as `packs[1].weight` and a bad sender as `sender.postcode` — enough to
 * find it without guessing which end of the shipment it came from.
 *
 * Carries no rules of its own. It is the marker that says a nested structure is worth looking
 * inside, which is not the default: most nested values on a response entity are never sent
 * anywhere and there is nothing to check.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Valid
{
}
