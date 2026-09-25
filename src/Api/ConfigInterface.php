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
     * Whether basic logging is enabled (used for error logging).
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool;

    /**
     * Whether extended logging of API requests is enabled for the given method.
     * The method is checked against @see self::getMethodsToLog()
     *
     * @param ApiMethodInterface $apiMethod
     *
     * @return bool
     */
    public function isExtendedLoggingEnabled(ApiMethodInterface $apiMethod): bool;

    /**
     * API methods to log. Empty by default.
     *
     * @return ApiMethodInterface[]
     */
    public function getMethodsToLog(): array;

    /**
     * Whether outgoing payloads are checked before they are sent.
     *
     * On by default. When off, the client-side rules are skipped and only the API checks the
     * payload. Use this if the SDK's rules ever disagree with the API, and please report it as a
     * bug.
     *
     * @return bool
     */
    public function validatePayloads(): bool;
}
