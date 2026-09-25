<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * Base class for everything this SDK throws.
 *
 * Catch this to handle any SDK failure in one place. Catch a subclass to tell apart bad
 * credentials, an unreachable host and a rejected payload.
 *
 * @api
 */
class Exception extends \Exception
{
}
