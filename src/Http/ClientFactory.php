<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http;

use GuzzleHttp\Client;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\ConfigInterface;
use Psr\Http\Client\ClientInterface;

/**
 * Builds a Guzzle client from the SDK configuration.
 *
 * Redirects are disabled. The API is a single POST endpoint that doesn't redirect, so a 3xx means
 * something is misconfigured. Following it would send the credentials to wherever it points.
 */
readonly class ClientFactory implements ClientFactoryInterface
{
    protected const string CONFIG_BASE_URI = 'base_uri';
    protected const string CONFIG_HEADERS = 'headers';
    protected const string CONFIG_TIMEOUT = 'timeout';
    protected const string CONFIG_ALLOW_REDIRECTS = 'allow_redirects';
    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->config = $configProvider->getConfig();
    }

    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface
    {
        return new Client([
            static::CONFIG_BASE_URI => $this->config->getApiUrl(),
            static::CONFIG_TIMEOUT => $this->config->getApiTimeout(),
            static::CONFIG_ALLOW_REDIRECTS => false,
            static::CONFIG_HEADERS => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
