<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * A message was constructed with values the SDK rejects before sending.
 *
 * Thrown locally, never because of an API response. It always means a bug in the calling code.
 *
 * @api
 */
class InvalidArgumentException extends Exception
{
}
