<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;


/**
 * Supplies the SDK configuration to everything that needs it.
 *
 * Indirection rather than passing a Config around, so a host application can resolve configuration
 * lazily — per tenant, per request, or from a container — at the moment it is first needed rather
 * than at wiring time.
 */
interface ConfigProviderInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
}
