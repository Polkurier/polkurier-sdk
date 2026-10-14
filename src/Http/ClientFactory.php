<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http;

use GuzzleHttp\Client;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Psr\Http\Client\ClientInterface;

/**
 * Builds a Guzzle client from the SDK configuration.
 *
 * Redirects are disabled. The API is a single POST endpoint that doesn't redirect, so a 3xx means
 * something is misconfigured. Following it would send the credentials to wherever it points.
 *
 * One client is built per distinct API URL and timeout, and reused for every call that has them.
 */
class ClientFactory implements ClientFactoryInterface
{
    protected const string CONFIG_BASE_URI = 'base_uri';
    protected const string CONFIG_HEADERS = 'headers';
    protected const string CONFIG_TIMEOUT = 'timeout';
    protected const string CONFIG_ALLOW_REDIRECTS = 'allow_redirects';
    /**
     * @param ConfigProviderInterface $configProvider read on every create(), so the client
     *        follows the configuration of the current call
     */
    public function __construct(protected readonly ConfigProviderInterface $configProvider) {}

    /**
     * The clients built so far, keyed by API URL and timeout.
     *
     * @var array<string, ClientInterface>
     */
    protected array $clients = [];

    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface
    {
        $config = $this->configProvider->getConfig();
        $key = $config->getApiUrl() . '|' . $config->getApiTimeout();

        return $this->clients[$key] ??= new Client([
            self::CONFIG_BASE_URI => $config->getApiUrl(),
            self::CONFIG_TIMEOUT => $config->getApiTimeout(),
            self::CONFIG_ALLOW_REDIRECTS => false,
            self::CONFIG_HEADERS => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
