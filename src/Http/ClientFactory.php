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
 * Redirects are disabled deliberately: the API is a single POST endpoint that never legitimately
 * redirects, so a 3xx means something has been misconfigured, and following it would silently
 * forward the credentials to wherever it points.
 */
final readonly class ClientFactory implements ClientFactoryInterface
{
    private const string CONFIG_BASE_URI = 'base_uri';
    private const string CONFIG_HEADERS = 'headers';
    private const string CONFIG_TIMEOUT = 'timeout';
    private const string CONFIG_ALLOW_REDIRECTS = 'allow_redirects';
    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

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
            self::CONFIG_BASE_URI => $this->config->getApiUrl(),
            self::CONFIG_TIMEOUT => $this->config->getApiTimeout(),
            self::CONFIG_ALLOW_REDIRECTS => false,
            self::CONFIG_HEADERS => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
