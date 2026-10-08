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
 * A DNS failure, refused connection, TLS problem or timeout. The API was never reached, so nothing
 * was processed and the call is safe to retry. With a
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
