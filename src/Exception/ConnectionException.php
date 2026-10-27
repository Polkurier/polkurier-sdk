<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * The request never completed.
 *
 * A DNS failure, refused connection, TLS problem or timeout. A request that timed out may still
 * have been processed, so check before retrying a call that changes data. With a
 * {@see \Polkurier\Webservice\Exception\WebserviceException}, by contrast, the API answered and
 * refused the call.
 *
 * The underlying PSR-18 exception is available through getPrevious().
 *
 * @api
 */
class ConnectionException extends Exception
{
}
