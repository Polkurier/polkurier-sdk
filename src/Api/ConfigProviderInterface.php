<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

/**
 * Supplies the SDK configuration to everything that needs it.
 *
 * It is asked on every call, so it can load the configuration lazily, e.g. from a container, or
 * return a different one per tenant or per request.
 *
 * @api
 */
interface ConfigProviderInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
}
