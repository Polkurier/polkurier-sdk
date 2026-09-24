<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Raised when the transport succeeded but the body is not a Polkurier API envelope — a proxy error
 * page, a redirect body, a truncated response. Carries the HTTP response so the caller can inspect
 * the status line and headers.
 *
 * @api
 */
class MalformedResponseException extends Exception
{
    /**
     * @param string $message
     * @param ResponseInterface $response
     * @param \Throwable|null $previous the underlying failure, where there was one
     */
    public function __construct(
        string $message,

        /**
         * @var ResponseInterface
         */
        private readonly ResponseInterface $response,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @param string $excerpt
     *
     * @return self
     */
    public static function notJson(ResponseInterface $response, string $excerpt): self
    {
        return new self(
            \sprintf(
                'Expected a JSON object from the Polkurier API, got HTTP %d with body: "%s"',
                $response->getStatusCode(),
                $excerpt
            ),
            $response
        );
    }

    /**
     * @param ResponseInterface $response
     * @param string $excerpt
     *
     * @return self
     */
    public static function missingStatus(ResponseInterface $response, string $excerpt): self
    {
        return new self(
            \sprintf(
                'Polkurier API response (HTTP %d) has no "status" field, body: "%s"',
                $response->getStatusCode(),
                $excerpt
            ),
            $response
        );
    }

    /**
     * The envelope parsed, but the payload inside it was not the shape the endpoint promises.
     *
     * Raised rather than returned so that a typed accessor can keep its type: a method declaring
     * it answers with an order should not hand back null because the API sent something else.
     *
     * @param ResponseInterface $response
     * @param string $apiMethod
     * @param string $expected
     * @param mixed $actual
     *
     * @return self
     */
    public static function unexpectedPayload(
        ResponseInterface $response,
        string $apiMethod,
        string $expected,
        mixed $actual,
    ): self {
        return new self(
            \sprintf(
                '%s answered with %s where %s was expected.',
                $apiMethod,
                \get_debug_type($actual),
                $expected,
            ),
            $response,
        );
    }

    /**
     * @param ResponseInterface $response
     * @param string $status
     *
     * @return self
     */
    public static function unknownStatus(ResponseInterface $response, string $status): self
    {
        return new self(
            \sprintf('Unknown Polkurier API response status: "%s"', $status),
            $response
        );
    }

    /**
     * The envelope parsed, but the payload could not be mapped onto the type the endpoint promises.
     *
     * Raised in place of the serializer's own exception so that everything leaving the SDK is one
     * of its own, and so the response that could not be mapped comes with it.
     *
     * @param ResponseInterface $response
     * @param string $expected
     * @param \Throwable $previous
     *
     * @return self
     */
    public static function notMappable(ResponseInterface $response, string $expected, \Throwable $previous): self
    {
        return new self(
            \sprintf('The response could not be mapped onto %s: %s', $expected, $previous->getMessage()),
            $response,
            $previous,
        );
    }
}
