<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice;

use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\ConfigInterface;

/**
 * Returns the same {@see ConfigInterface} to every part of the SDK that asks for it.
 *
 * The services, the HTTP client factory and the request factory get their configuration from a
 * provider. With your own provider you can load configuration lazily, e.g. from a container, a
 * database row or a per-tenant context, when it is first needed. This class covers the simple
 * case where the configuration is already known.
 */
readonly class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        /**
         * @var ConfigInterface
         */
        protected ConfigInterface $config,
    ) {}

    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}
