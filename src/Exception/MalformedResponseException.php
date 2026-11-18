<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when the request went through but the body is not a Polkurier API envelope, e.g. a proxy
 * error page, a redirect body or a truncated response, or when the envelope's payload doesn't have
 * the shape the method promises. The HTTP response is attached so you can inspect the status line
 * and headers.
 *
 * @api
 */
class MalformedResponseException extends Exception
{
    /**
     * @param string $message
     * @param ResponseInterface $response
     * @param \Throwable|null $previous the underlying failure, if any
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
     * The body could not be read as an API response at all, or its payload could not be read as
     * the class the method promises.
     *
     * The serializer knows neither that the body came over HTTP nor what the API answered with,
     * so the status line is added here.
     *
     * @param ResponseInterface $response
     * @param InvalidArgumentException $cause what the serializer refused, and why
     *
     * @return self
     */
    public static function makeForUnreadableBody(ResponseInterface $response, InvalidArgumentException $cause): self
    {
        return new self(
            \sprintf('Polkurier API response (HTTP %d): %s', $response->getStatusCode(), $cause->getMessage()),
            $response,
            $cause,
        );
    }

    /**
     * The envelope parsed, but the payload inside it doesn't have the shape the endpoint promises.
     *
     * Thrown so typed methods keep their return type. A method that returns an order won't
     * return null because the API sent something else.
     *
     * @param ResponseInterface $response
     * @param string $apiMethod
     * @param string $expected
     * @param mixed $actual
     *
     * @return self
     */
    public static function makeForUnexpectedPayload(
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
}
