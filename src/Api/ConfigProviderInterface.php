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
 * Lets you load configuration lazily (per tenant, per request, or from a container) when it is
 * first needed.
 */
interface ConfigProviderInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
}
