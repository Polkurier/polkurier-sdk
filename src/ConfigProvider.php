<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice;

use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\ConfigInterface;

/**
 * Returns the same {@see ConfigInterface} to every part of the SDK that asks for it.
 *
 * The services, the HTTP client factory and the request factory ask a provider for their
 * configuration on every call. With your own provider you can load it lazily, e.g. from a
 * container, a database row or a per-tenant context. This class covers the simple case where the
 * configuration is already known.
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
