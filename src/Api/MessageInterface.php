<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

/**
 * Marker for anything that can travel in a request or response body.
 *
 * Deliberately empty: it constrains what may be handed to the request factory without imposing any
 * shape on it.
 */
interface MessageInterface
{
}
