<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

use GuzzleHttp\Psr7\Response;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * Reads a payload the way a service call reads one: wrapped in the envelope the API sends, through
 * a response factory built on the given serializer settings.
 */
final class Responses
{
    /**
     * @param string $format the response class, or one followed by `[]` for a list of it
     * @param mixed $payload what the envelope's `response` key holds
     * @param SerializerConfig $config
     *
     * @return mixed what the format asked for
     */
    public static function read(
        string $format,
        mixed $payload,
        SerializerConfig $config = new SerializerConfig(),
    ): mixed {
        return self::readWrapped($format, $payload, $config)->getEnvelope()->response;
    }

    /**
     * The same read, kept paired with the drift it recorded and the HTTP response it came from.
     *
     * @param string $format
     * @param mixed $payload
     * @param SerializerConfig $config
     *
     * @return ResponseWrapper
     */
    public static function readWrapped(
        string $format,
        mixed $payload,
        SerializerConfig $config = new SerializerConfig(),
    ): ResponseWrapper {
        // A decimal the API sends as 250.0 stays a decimal, so a payload that holds one reads the
        // way the API's body does rather than as the integer json_encode would write.
        $body = \json_encode(
            ['status' => 'success', 'response' => $payload],
            \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION,
        );

        return self::readBody($format, $body, $config);
    }

    /**
     * Reads a body spelled out in the test, for what a payload cannot say: whitespace, escapes, a
     * float spelling, or an envelope that is not a success.
     *
     * @param string $format
     * @param string $body
     * @param SerializerConfig $config
     *
     * @return ResponseWrapper
     */
    public static function readBody(
        string $format,
        string $body,
        SerializerConfig $config = new SerializerConfig(),
    ): ResponseWrapper {
        return (new ResponseFactory(new MessageSerializer(new SerializerProvider($config))))
            ->create(new Response(200, [], $body), $format);
    }
}
