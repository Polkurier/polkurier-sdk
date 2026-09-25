<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http;

use Psr\Http\Client\ClientInterface;

/**
 * Supplies the PSR-18 client the services send requests through.
 *
 * The client is built once the configuration is known. You can supply your own client, e.g. one
 * with retries, connection pooling or request tracing.
 */
interface ClientFactoryInterface
{
    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface;
}
