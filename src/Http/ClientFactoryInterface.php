<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http;

use Psr\Http\Client\ClientInterface;

/**
 * Supplies the PSR-18 client the services send through.
 *
 * Indirection rather than a plain client so that the client is built once the configuration is
 * known, and so a host application can substitute its own — one with retries, connection pooling
 * or request tracing — without the services knowing.
 */
interface ClientFactoryInterface
{
    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface;
}
