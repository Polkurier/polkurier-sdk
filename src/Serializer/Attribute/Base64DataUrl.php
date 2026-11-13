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
}
