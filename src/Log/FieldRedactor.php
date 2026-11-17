<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;

/**
 * Replaces the values of the fields marked with one attribute with {@see self::MASK} in request
 * and response bodies.
 *
 * A body is walked beside the classes that wrote it, so a key is hidden or kept by what the class
 * declaring it marks it as, read by {@see FieldMarkReader}: `id` is hidden where a class declares
 * it a secret and logged where it is a record id. A key no class the walk knows declares is
 * logged, and so is an empty value, which would only say the field was there.
 *
 * A body that isn't JSON is left as it is.
 */
abstract class FieldRedactor
{
    public const string MASK = '***';

    /**
     * The attribute this redactor acts on.
     *
     * @return class-string
     */
    abstract protected function actsOn(): string;

    /**
     * The body as a string, with the marked fields hidden.
     *
     * @param MessageInterface $message a request or a response
     * @param array<string, class-string> $classes the class the value under each top-level key
     *        holds, see {@see self::redact()}
     * @param class-string|null $whenNoneApply the class the whole body holds when it carries none
     *        of those keys
     *
     * @return string
     */
    public function body(MessageInterface $message, array $classes, ?string $whenNoneApply = null): string
    {
        return $this->redactJson((string)$message->getBody(), $classes, $whenNoneApply);
    }

    /**
     * A copy of the request or response with the marked fields in its body hidden.
     *
     * @template T of MessageInterface
     *
     * @param T $message
     * @param array<string, class-string> $classes
     * @param class-string|null $whenNoneApply
     *
     * @return T
     */
    public function message(MessageInterface $message, array $classes, ?string $whenNoneApply = null): MessageInterface
    {
        /** @var T */
        return $message->withBody(Utils::streamFor($this->body($message, $classes, $whenNoneApply)));
    }

    /**
     * @param string $json
     * @param array<string, class-string> $classes
     * @param class-string|null $whenNoneApply
     *
     * @return string
     */
    public function redactJson(string $json, array $classes, ?string $whenNoneApply = null): string
    {
        $data = \json_decode($json, true);
        if (!\is_array($data)) {
            return $json;
        }

        return LogJson::encode($this->redact($data, $classes, $whenNoneApply)) ?? $json;
    }

    /**
     * @param array<array-key, mixed> $data the decoded body
     * @param array<string, class-string> $classes the class the value under each top-level key
     *        holds. The envelope's own keys hold the method name and the status, and the classes
     *        of the payload and of the credentials are known to the caller that sent or asked for
     *        them, not to the envelope. A key named here is walked as that class, every other key
     *        with no class, which hides nothing below it
     *
     * @return array<array-key, mixed>
     */
    public function redact(array $data, array $classes, ?string $whenNoneApply = null): array
    {
        foreach (\array_keys($classes) as $key) {
            if (\array_key_exists($key, $data)) {
                return $this->walk($data, [], $classes);
            }
        }

        if ($whenNoneApply === null) {
            return $this->walk($data, [], $classes);
        }

        return $this->walk(
            $data,
            FieldMarkReader::readMarksOf($whenNoneApply),
            $classes + FieldMarkReader::readClassesOf($whenNoneApply),
            FieldMarkReader::readMarksOfForeignKeysOf($whenNoneApply),
        );
    }

    /**
     * Whether the value under a key is hidden.
     *
     * @param string $key
     * @param list<class-string> $marks what the class declaring the key marks it as
     *
     * @return bool
     */
    protected function hides(string $key, array $marks): bool
    {
        return \in_array($this->actsOn(), $marks, true);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<string, list<class-string>> $marks the marks of the class this level holds
     * @param array<string, class-string> $classes the classes the keys of this level hold
     * @param list<class-string> $foreign the marks governing a key this level's class does not
     *        write, for a class that inlines a map whose keys it cannot name
     *
     * @return array<array-key, mixed>
     */
    protected function walk(array $data, array $marks, array $classes, array $foreign = []): array
    {
        foreach ($data as $key => $value) {
            if (!\is_string($key)) {
                // A JSON list holds the same class repeated, so its entries are read as the list is.
                $data[$key] = \is_array($value) ? $this->walk($value, $marks, $classes, $foreign) : $value;
            } elseif ($this->hides($key, $marks[$key] ?? $foreign) && $value !== null && $value !== '' && $value !== []) {
                $data[$key] = self::MASK;
            } elseif (\is_array($value)) {
                $data[$key] = $this->descend($value, $classes[$key] ?? null);
            }
        }

        return $data;
    }

    /**
     * Walks the value of a field as the class that field holds, or, where the walk knows of none,
     * as a body whose keys declare nothing.
     *
     * @param array<array-key, mixed> $data
     * @param class-string|null $class
     *
     * @return array<array-key, mixed>
     */
    protected function descend(array $data, ?string $class): array
    {
        return $class === null
            ? $this->walk($data, [], [])
            : $this->walk(
                $data,
                FieldMarkReader::readMarksOf($class),
                FieldMarkReader::readClassesOf($class),
                FieldMarkReader::readMarksOfForeignKeysOf($class),
            );
    }
}
