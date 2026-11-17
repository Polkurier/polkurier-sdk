<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns an HTTP response from the API into a {@see ResponseWrapper}.
 *
 * The body is read by the serializer the whole SDK reads and writes with. This adds what only an
 * HTTP exchange has: the response the body came in, which it keeps on the wrapper and names in a
 * {@see MalformedResponseException}.
 */
readonly class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @param MessageSerializer $serializer the one the whole SDK writes and reads with. The SDK's
     *        own, not {@see \Polkurier\Webservice\Api\SerializerInterface}: what it applies is
     *        part of what the SDK promises, so it is not a seam
     */
    public function __construct(protected MessageSerializer $serializer) {}

    /**
     * The response as the SDK reads it: the envelope, and how the body differed from what the SDK
     * expects.
     *
     * @param ResponseInterface $httpResponse
     * @param string $responseFormat
     *
     * @return ResponseWrapper
     * @throws MalformedResponseException when the body is not a response the SDK can read
     */
    public function create(ResponseInterface $httpResponse, string $responseFormat): ResponseWrapper
    {
        // Read from the start, so the raw body is complete even if the client left the stream
        // elsewhere.
        $body = $httpResponse->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        try {
            $read = $this->serializer->readResponse($body->getContents(), $responseFormat);
        } catch (InvalidArgumentException $e) {
            throw MalformedResponseException::unreadableBody($httpResponse, $e);
        }

        return new ResponseWrapper($httpResponse, $read->envelope, $read->drift);
    }
}
