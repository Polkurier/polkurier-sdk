<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use GuzzleHttp\Psr7\Utils;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Psr\Http\Message\MessageInterface;

/**
 * Prepares request and response bodies for a log, as the configuration says.
 *
 * File contents are always taken out, and saved to separate files when the configuration allows.
 * Secrets and personal data are hidden unless the configuration allows logging them.
 *
 * Each message is prepared once and the result reused, so a body that goes into both the log
 * message and its context doesn't save its files twice.
 */
class LogSanitizer
{
    /**
     * @var \WeakMap<MessageInterface, array<string, string>>
     */
    protected \WeakMap $prepared;

    /**
     * @param SensitiveDataRedactor $sensitiveData
     * @param PersonalDataRedactor $personalData
     * @param LoggedFileWriter $files
     */
    public function __construct(
        /**
         * @var SensitiveDataRedactor
         */
        protected readonly SensitiveDataRedactor $sensitiveData = new SensitiveDataRedactor(),

        /**
         * @var PersonalDataRedactor
         */
        protected readonly PersonalDataRedactor $personalData = new PersonalDataRedactor(),

        /**
         * @var LoggedFileWriter
         */
        protected readonly LoggedFileWriter $files = new LoggedFileWriter(),
    ) {
        $this->prepared = new \WeakMap();
    }

    /**
     * The body as it goes into a log.
     *
     * @param MessageInterface $message a request or a response
     * @param ConfigInterface $config
     * @param ApiMethodInterface $apiMethod
     *
     * @return string
     */
    public function body(MessageInterface $message, ConfigInterface $config, ApiMethodInterface $apiMethod): string
    {
        $key = \implode('|', [
            (int)$config->isSensitiveDataLoggingEnabled(),
            (int)$config->isPersonalDataLoggingEnabled(),
            (int)$config->isFileLoggingEnabled(),
            $config->getLogFilesDirectory(),
        ]);

        $prepared = $this->prepared[$message] ?? [];
        if (!isset($prepared[$key])) {
            $prepared[$key] = $this->prepare((string)$message->getBody(), $config, $apiMethod);
            $this->prepared[$message] = $prepared;
        }

        return $prepared[$key];
    }

    /**
     * A copy of the request or response with its body as it goes into a log.
     *
     * @template T of MessageInterface
     *
     * @param T $message
     * @param ConfigInterface $config
     * @param ApiMethodInterface $apiMethod
     *
     * @return T
     */
    public function message(
        MessageInterface $message,
        ConfigInterface $config,
        ApiMethodInterface $apiMethod,
    ): MessageInterface {
        /** @var T */
        return $message->withBody(Utils::streamFor($this->body($message, $config, $apiMethod)));
    }

    /**
     * Whether objects that hold a body's data, such as the payload, can go into a log as they are.
     *
     * They can't be prepared like a body, so they are only logged when nothing in them would be
     * hidden.
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    public function allowsObjects(ConfigInterface $config): bool
    {
        return $config->isSensitiveDataLoggingEnabled() && $config->isPersonalDataLoggingEnabled();
    }

    /**
     * @param string $body
     * @param ConfigInterface $config
     * @param ApiMethodInterface $apiMethod
     *
     * @return string
     */
    protected function prepare(string $body, ConfigInterface $config, ApiMethodInterface $apiMethod): string
    {
        $data = \json_decode($body, true);
        if (!\is_array($data)) {
            return $body;
        }

        $data = $this->files->process(
            $data,
            $config->isFileLoggingEnabled(),
            $config->getLogFilesDirectory(),
            $apiMethod,
        );
        if (!$config->isSensitiveDataLoggingEnabled()) {
            $data = $this->sensitiveData->redact($data, $apiMethod);
        }
        if (!$config->isPersonalDataLoggingEnabled()) {
            $data = $this->personalData->redact($data, $apiMethod);
        }

        return LogJson::encode($data) ?? $body;
    }
}
