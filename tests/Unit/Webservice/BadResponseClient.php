<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A client that raises on an error status the way a Guzzle client does, with the response on the
 * exception and its body quoted in the exception's message.
 */
final class BadResponseClient implements ClientInterface, ClientFactoryInterface
{
    /**
     * @param string $body the response body the exception carries and quotes
     * @param int $status
     */
    public function __construct(
        private readonly string $body,
        private readonly int $status = 400,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw RequestException::create($request, new Response($this->status, [], $this->body));
    }

    public function create(): ClientInterface
    {
        return $this;
    }
}
