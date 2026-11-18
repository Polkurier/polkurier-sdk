<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\ReadResponse;

/**
 * Writes what the client sends and reads what it receives.
 *
 * The request and response factories build every body and read every response through this, so
 * what it writes is what a call sends and what it reads is what a call returns.
 * {@see \Polkurier\Webservice\PolkurierWebservice::serializer()} hands out the instance they use:
 * keep a record of a call before making it, show it, read a stored payload back into its objects,
 * or read a response body the SDK didn't fetch.
 *
 * The SDK hands this out and never takes one: its own serializer is not replaceable, because the
 * quirks it applies are part of what the SDK promises. An implementation of your own serves your
 * code; it changes nothing the SDK sends.
 *
 * @api
 */
interface SerializerInterface
{
    /**
     * Response format for methods that only acknowledge.
     *
     * Some of them answer with `true`, others with an empty array, so the payload can't be cast.
     * Getting a payload at all means the envelope said `success`, so the result is true unless the
     * API explicitly returned false. An empty string is read the same way.
     */
    public const string FORMAT_BOOLEAN = 'boolean';

    /**
     * Response format for an answer with no fixed shape.
     *
     * The decoded payload is returned as it came.
     */
    public const string FORMAT_RAW_ARRAY = 'array';

    /**
     * Response format for an answer that is a map or list of plain strings.
     */
    public const string FORMAT_STRING_LIST = 'string[]';

    /**
     * A message as the client would send it, without the API method or the credentials that go
     * around it.
     *
     * @param MessageInterface $message
     *
     * @return string
     * @throws InvalidArgumentException when the message cannot be written
     */
    public function encode(MessageInterface $message): string;

    /**
     * A payload read back into the class it was written from.
     *
     * Fields the given class doesn't declare are ignored, as in a response, so a payload stored by
     * an older version still reads. A field written by a method rather than a property is one of
     * them: an order's additional services are sent as the flag map
     * {@see \Polkurier\Webservice\Entity\Order::getCourierServiceMap()} writes, and a payload
     * read back carries none of them, so a stored payload is re-sent rather than rebuilt.
     *
     * @template T of MessageInterface
     *
     * @param string $payload
     * @param class-string<T> $type
     *
     * @return T
     * @throws InvalidArgumentException when the payload cannot be read as that class
     */
    public function decode(string $payload, string $type): MessageInterface;

    /**
     * A response body read into the envelope the API sent, with how it differed from what the
     * SDK expects.
     *
     * @param string $body the response body, as it came
     * @param string $responseFormat the class the payload is read into, or the format a service
     *        asks for when the payload is not an object: {@see self::FORMAT_BOOLEAN},
     *        {@see self::FORMAT_RAW_ARRAY}, {@see self::FORMAT_STRING_LIST}, or a class name with
     *        `[]` after it for a list of that class
     *
     * @return ReadResponse
     * @throws InvalidArgumentException when the body is not an API response, or its payload cannot
     *         be read as that class
     */
    public function readResponse(string $body, string $responseFormat): ReadResponse;
}
