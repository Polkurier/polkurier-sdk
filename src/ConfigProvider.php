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
 * Hands the same {@see ConfigInterface} to every collaborator that needs it.
 *
 * The services, the HTTP client factory and the request factory each resolve configuration through
 * a provider rather than receiving a Config directly. That indirection exists so a host application
 * can supply configuration lazily — reading it from a container, a database row or a tenant-scoped
 * context at the moment it is first needed, rather than at wiring time. This implementation is the
 * trivial case: configuration that is already known.
 */
final readonly class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        /**
         * @var ConfigInterface
         */
        private ConfigInterface $config,
    ) {}

    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
}
