<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http;

use Psr\Http\Client\ClientInterface;

/**
 * Supplies the PSR-18 client the services send requests through.
 *
 * The client is built once the configuration is known. You can supply your own client, e.g. one
 * with retries, connection pooling or request tracing.
 *
 * Requests carry only the path `/`, so the client must resolve it against
 * {@see \Polkurier\Webservice\Api\ConfigInterface::getApiUrl()}, as
 * {@see \Polkurier\Webservice\Http\ClientFactory} does. It must not change the request's
 * `Content-Type: application/json`: the API answers any other value, a charset parameter included,
 * with UNSUPPORTED_MEDIA_TYPE.
 */
interface ClientFactoryInterface
{
    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface;
}
