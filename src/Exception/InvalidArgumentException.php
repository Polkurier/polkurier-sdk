<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * A message was constructed with values the SDK rejects before sending.
 *
 * Raised locally, never in response to anything the API said, so it always indicates a bug in the
 * calling code rather than a transient failure.
 *
 * @api
 */
class InvalidArgumentException extends Exception
{
}
