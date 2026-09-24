<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;

/**
 * How the SDK should talk to the API.
 *
 * @see \Polkurier\Webservice\Config for the standard implementation and its defaults.
 */
interface ConfigInterface
{
    /**
     * @return AuthorizationMessage
     */
    public function getAuthorization(): AuthorizationMessage;

    /**
     * @return string
     */
    public function getApiUrl(): string;



    /**
     * @return int
     */
    public function getApiTimeout(): int;

    /**
     * @return string
     */
    public function getPlatform(): string;

    /**
     * @return string
     */
    public function getPlatformVersion(): string;

    /**
     * @return bool
     */
    public function isSandbox(): bool;

    /**
     * Is base logging enabled (used for error logging)
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool;

    /**
     * Checks if extended logging of API requests is enabled for given method
     * The method is checked against @see self::getMethodsToLog()
     *
     * @param ApiMethodInterface $apiMethod
     *
     * @return bool
     */
    public function isExtendedLoggingEnabled(ApiMethodInterface $apiMethod): bool;

    /**
     * Returns API methods which will be logged. The default is empty list
     *
     * @return ApiMethodInterface[]
     */
    public function getMethodsToLog(): array;

    /**
     * Whether outgoing payloads are checked before they are sent.
     *
     * On by default. Turning it off skips the client-side rules entirely and lets the API be the
     * only judge — worth doing if the SDK's rules ever disagree with the service, which should be
     * reported as a bug rather than worked around permanently.
     *
     * @return bool
     */
    public function validatePayloads(): bool;
}
