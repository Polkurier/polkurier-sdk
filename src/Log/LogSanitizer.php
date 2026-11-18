<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use GuzzleHttp\Psr7\Utils;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Psr\Http\Message\MessageInterface;

/**
 * Prepares request and response bodies for a log, as the configuration says.
 *
 * File contents are always taken out, and saved to separate files when the configuration allows.
 * Secrets and personal data are hidden unless the configuration allows logging them.
 *
 * A body that is not an API envelope — a gateway's error page, a redirect body, a truncated
 * response — has no keys to hide anything by, and it is the body you most need to read when a
 * call fails, so it is logged as it arrived, shortened to {@see self::EXCERPT_LENGTH}. In an
 * envelope, only a field whose class marks it is hidden; a value under a key no class the walk
 * knows declares is logged.
 *
 * Each message is prepared once and the result reused, so a body that goes into both the log
 * message and its context doesn't save its files twice.
 */
class LogSanitizer
{
    /**
     * How much of a body that could not be read goes into the log.
     *
     * Enough to show a gateway's error page or where a truncated response stops, bounded because
     * such a body can be any size and any content type.
     */
    public const int EXCERPT_LENGTH = 512;

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
     * @param array<string, class-string> $classes the class the value under each top-level key of
     *        the body holds, see {@see FieldRedactor::redact()}
     * @param class-string|null $whenNoneApply the class the whole body holds when it carries none
     *        of those keys
     *
     * @return string
     */
    public function sanitizeBody(
        MessageInterface $message,
        ConfigInterface $config,
        ApiMethodInterface $apiMethod,
        array $classes,
        ?string $whenNoneApply = null,
    ): string {
        // The classes are part of the key: the same message read as 2 different payloads is 2
        // different bodies, and the second would otherwise be served the first one's answer.
        $key = \implode('|', [
            (int)$config->isSensitiveDataLoggingEnabled(),
            (int)$config->isPersonalDataLoggingEnabled(),
            (int)$config->isFileLoggingEnabled(),
            $config->getLogFilesDirectory(),
            \implode(',', \array_map(static fn(string $k, string $c): string => $k . '=' . $c,
                \array_keys($classes), \array_values($classes))),
            (string)$whenNoneApply,
        ]);

        $prepared = $this->prepared[$message] ?? [];
        if (!isset($prepared[$key])) {
            $prepared[$key] = $this->prepare(
                (string)$message->getBody(),
                $config,
                $apiMethod,
                $classes,
                $whenNoneApply,
            );
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
     * @param array<string, class-string> $classes
     * @param class-string|null $whenNoneApply the class the whole body holds when it carries none
     *        of those keys
     *
     * @return T
     */
    public function sanitizeMessage(
        MessageInterface $message,
        ConfigInterface $config,
        ApiMethodInterface $apiMethod,
        array $classes,
        ?string $whenNoneApply = null,
    ): MessageInterface {
        return $message->withBody(
            Utils::streamFor($this->sanitizeBody($message, $config, $apiMethod, $classes, $whenNoneApply)),
        );
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
     * @param array<string, class-string> $classes
     * @param class-string|null $whenNoneApply the class the whole body holds when it carries none
     *        of those keys
     *
     * @return string
     */
    protected function prepare(
        string $body,
        ConfigInterface $config,
        ApiMethodInterface $apiMethod,
        array $classes,
        ?string $whenNoneApply = null,
    ): string {
        $data = \json_decode($body, true);
        if (!\is_array($data)) {
            // Not an envelope, so there are no keys to hide anything by, and this is the body a
            // failed call is diagnosed from. It is shortened rather than withheld.
            $trimmed = \trim($body);
            if (\strlen($trimmed) <= self::EXCERPT_LENGTH) {
                return $trimmed;
            }

            // The limit is in bytes, so the cut can land inside a character. The partial tail is
            // dropped, because a formatter that encodes the record as JSON fails on invalid UTF-8
            // and can drop the whole record. A character is at most 4 bytes, so a longer walk back
            // would mean the body was not UTF-8 to begin with.
            $cut = \substr($trimmed, 0, self::EXCERPT_LENGTH);
            for ($dropped = 0; $dropped < 3 && \preg_match('//u', $cut) !== 1; ++$dropped) {
                $cut = \substr($cut, 0, -1);
            }

            return $cut . '…';
        }

        $data = $this->files->process(
            $data,
            $config->isFileLoggingEnabled(),
            $config->getLogFilesDirectory(),
            $apiMethod,
        );
        if (!$config->isSensitiveDataLoggingEnabled()) {
            $data = $this->sensitiveData->redact($data, $classes, $whenNoneApply);
        }
        if (!$config->isPersonalDataLoggingEnabled()) {
            $data = $this->personalData->redact($data, $classes, $whenNoneApply);
        }

        return LogJson::encode($data) ?? $body;
    }
}
