<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

/**
 * Marker for anything that can be sent in a request or response body.
 *
 * It has no methods. It only limits what can be passed to the request factory.
 */
interface MessageInterface
{
}
