<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use GuzzleHttp\Psr7\Utils;
use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Psr\Http\Message\MessageInterface;

/**
 * Replaces the values of chosen fields with {@see self::MASK} in request and response bodies.
 *
 * A field is chosen by its key, at any depth. Some keys mean different things in different
 * methods, e.g. `number` is a bank account number in one and an order number in another. Those are
 * chosen per method. A body that isn't JSON is left as it is, and so are empty values.
 */
class FieldRedactor
{
    public const string MASK = '***';

    /**
     * @param string[] $fields keys hidden in every method
     * @param array<string, string[]> $methodFields keys hidden only in the given methods, keyed by
     *        the method's wire name
     */
    public function __construct(
        /**
         * @var string[]
         */
        protected readonly array $fields = [],

        /**
         * @var array<string, string[]>
         */
        protected readonly array $methodFields = [],
    ) {}

    /**
     * The body as a string, with the chosen fields hidden.
     *
     * @param MessageInterface $message a request or a response
     * @param ApiMethodInterface|null $apiMethod the method it belongs to, for the per-method fields
     *
     * @return string
     */
    public function body(MessageInterface $message, ?ApiMethodInterface $apiMethod = null): string
    {
        return $this->redactJson((string)$message->getBody(), $apiMethod);
    }

    /**
     * A copy of the request or response with the chosen fields in its body hidden.
     *
     * @template T of MessageInterface
     *
     * @param T $message
     * @param ApiMethodInterface|null $apiMethod
     *
     * @return T
     */
    public function message(MessageInterface $message, ?ApiMethodInterface $apiMethod = null): MessageInterface
    {
        /** @var T */
        return $message->withBody(Utils::streamFor($this->body($message, $apiMethod)));
    }

    /**
     * @param string $json
     * @param ApiMethodInterface|null $apiMethod
     *
     * @return string
     */
    public function redactJson(string $json, ?ApiMethodInterface $apiMethod = null): string
    {
        $data = \json_decode($json, true);
        if (!\is_array($data)) {
            return $json;
        }

        return LogJson::encode($this->redact($data, $apiMethod)) ?? $json;
    }

    /**
     * @param array<array-key, mixed> $data
     * @param ApiMethodInterface|null $apiMethod
     *
     * @return array<array-key, mixed>
     */
    public function redact(array $data, ?ApiMethodInterface $apiMethod = null): array
    {
        $fields = $this->fields;
        if ($apiMethod !== null) {
            $fields = [...$fields, ...($this->methodFields[$apiMethod->value] ?? [])];
        }

        return $this->walk($data, $fields);
    }

    /**
     * Whether the value under a key is hidden.
     *
     * @param string[] $fields
     */
    protected function hides(string $key, array $fields): bool
    {
        return \in_array($key, $fields, true);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param string[] $fields
     *
     * @return array<array-key, mixed>
     */
    protected function walk(array $data, array $fields): array
    {
        foreach ($data as $key => $value) {
            if (\is_string($key) && $this->hides($key, $fields)
                && $value !== null && $value !== '' && $value !== []
            ) {
                $data[$key] = self::MASK;
            } elseif (\is_array($value)) {
                $data[$key] = $this->walk($value, $fields);
            }
        }

        return $data;
    }
}
