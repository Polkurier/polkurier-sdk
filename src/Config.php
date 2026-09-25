<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

/**
 * How the SDK should talk to the API.
 *
 * Only the credentials and the platform name and version are required. Everything else has a
 * default. The platform name and version identify your integration, e.g. to support, so use
 * something stable and recognisable.
 *
 * Endpoint: if apiUrl is set, it is used. Otherwise the sandbox flag picks the sandbox or the
 * production host.
 */
readonly class Config implements ConfigInterface
{
    public const string SERIALIZATION_FORMAT = 'json';
    public const string API_URL = 'https://api.polkurier.pl';
    public const string SANDBOX_API_URL = 'https://api-sandbox.polkurier.pl';

    /**
     * @param AuthorizationMessage $authorization
     * @param string $platform
     * @param string $platformVersion
     * @param string|null $apiUrl
     * @param int $apiTimeout
     * @param bool $sandbox
     * @param bool $loggingEnabled
     * @param array $methodsToLog
     * @param bool $validatePayloads
     */
    public function __construct(
        /**
         * @var AuthorizationMessage
         */
        protected AuthorizationMessage $authorization,

        /**
         * @var string
         */
        protected string $platform,

        /**
         * @var string
         */
        protected string $platformVersion,

        /**
         * @var string
         */
        protected ?string $apiUrl = null,

        /**
         * @var int
         */
        protected int $apiTimeout = 30,

        /**
         * @var bool
         */
        protected bool $sandbox = false,

        /**
         * @var bool
         */
        protected bool $loggingEnabled = true,

        /**
         * @var ApiMethodInterface[]
         */
        protected array $methodsToLog = [],

        /**
         * @var bool
         */
        protected bool $validatePayloads = true,
    ) {}

    /**
     * @return AuthorizationMessage
     */
    public function getAuthorization(): AuthorizationMessage
    {
        return $this->authorization;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @return string
     */
    public function getPlatformVersion(): string
    {
        return $this->platformVersion;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl ?? ($this->isSandbox() ? static::SANDBOX_API_URL : static::API_URL);
    }

    /**
     * @return int
     */
    public function getApiTimeout(): int
    {
        return $this->apiTimeout;
    }

    /**
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled;
    }

    /**
     * @param ApiMethodInterface $apiMethod
     *
     * @return bool
     */
    public function isExtendedLoggingEnabled(ApiMethodInterface $apiMethod): bool
    {
        return $this->isLoggingEnabled() && \in_array($apiMethod, $this->getMethodsToLog());
    }

    /**
     * @return ApiMethodInterface[]
     */
    public function getMethodsToLog(): array
    {
        return $this->methodsToLog;
    }

    /**
     * @return bool
     */
    public function validatePayloads(): bool
    {
        return $this->validatePayloads;
    }
}
