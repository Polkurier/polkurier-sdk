<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * Validates the nested value in this property using the rules it declares.
 *
 * An object is checked directly. For a list, every entry is checked. Reported fields include the
 * path, e.g. `packs[1].weight` or `sender.postcode`.
 *
 * It has no rules of its own. Nested values are not checked unless marked with this attribute,
 * since most nested values on a response entity are never sent.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Valid
{
}
