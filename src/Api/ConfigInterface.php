<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;

/**
 * How the SDK should talk to the API.
 *
 * @see \Polkurier\Webservice\Config for the standard implementation and its defaults.
 *
 * @api
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
     * Whether the given method's requests and responses are logged in full: basic logging is on
     * and the method is in {@see self::getMethodsToLog()}.
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
     * Whether secrets are logged as they are.
     *
     * Off by default: every field {@see \Polkurier\Webservice\Log\SensitiveDataRedactor} names is
     * replaced with `***` in logged bodies, and objects that could hold one are left out of the log
     * context. On, it logs the full payloads, and the logs are then as sensitive as the credentials
     * in them.
     *
     * @return bool
     */
    public function isSensitiveDataLoggingEnabled(): bool;

    /**
     * Whether personal data is logged as it is.
     *
     * Off by default: every field {@see \Polkurier\Webservice\Log\PersonalDataRedactor} names is
     * replaced with `***` in logged bodies.
     *
     * @return bool
     */
    public function isPersonalDataLoggingEnabled(): bool;

    /**
     * Whether files in requests and responses are saved for the log.
     *
     * File contents, such as labels and attachments, are never written into a log entry. When this
     * is on, each file is saved in {@see self::getLogFilesDirectory()} and the entry gives its
     * path. When it is off, which is the default, the entry only gives its size.
     *
     * @return bool
     */
    public function isFileLoggingEnabled(): bool;

    /**
     * Where files are saved when {@see self::isFileLoggingEnabled()} is on.
     *
     * @return string
     */
    public function getLogFilesDirectory(): string;

    /**
     * Whether outgoing payloads are checked before they are sent.
     *
     * On by default. When off, the client-side rules are skipped and only the API checks the
     * payload. A disagreement between the SDK's rules and the API is a bug in the SDK.
     *
     * @return bool
     */
    public function validatePayloads(): bool;
}
