<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * The API returns this field but doesn't take it, so it is left out of what the SDK sends.
 *
 * Applied by {@see \Polkurier\Webservice\Serializer\OmitEmptyNormalizer}.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ResponseOnly
{
}
