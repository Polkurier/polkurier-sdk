<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Testing;

use GuzzleHttp\Psr7\Response;
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An HTTP client that returns a fixed body and records the requests it receives.
 *
 * It tests the service layer end to end (request building, serialization, response parsing)
 * without a network. It is also its own factory, so it can be passed straight to a service
 * constructor.
 *
 * @api
 */
final class RecordingClient implements ClientInterface, ClientFactoryInterface
{
    /**
     * @var RequestInterface[]
     */
    private array $requests = [];

    /**
     * @param string $body the JSON envelope to return
     * @param int $status the HTTP status to return. The real API uses 200 for everything,
     *                    including errors
     */
    public function __construct(
        private string $body = '{"status":"success","response":true}',
        private int $status = 200,
    ) {}

    /**
     * Sets what the next call is answered with.
     *
     * The status is settable with the body, so one client can be driven through a success and a
     * transport failure in the same test.
     *
     * @param string $body
     * @param int|null $status the HTTP status to return from now on, or null to keep the current one
     *
     * @return void
     */
    public function willAnswerWith(string $body, ?int $status = null): void
    {
        $this->body = $body;
        if ($status !== null) {
            $this->status = $status;
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return new Response($this->status, ['Content-Type' => 'application/json'], $this->body);
    }

    /**
     * @return ClientInterface
     */
    public function create(): ClientInterface
    {
        return $this;
    }

    /**
     * The most recent request, or null before the first call.
     *
     * @return RequestInterface|null
     */
    public function getLastRequest(): ?RequestInterface
    {
        return $this->requests[\count($this->requests) - 1] ?? null;
    }

    /**
     * The decoded request body of the most recent call. Empty before the first call, and for a
     * body that is not a JSON object or array.
     *
     * @return array<array-key, mixed>
     */
    public function readLastPayload(): array
    {
        $request = $this->getLastRequest();
        if ($request === null) {
            return [];
        }
        $decoded = \json_decode((string)$request->getBody(), true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @return int
     * @phpstan-impure
     */
    public function countRequests(): int
    {
        return \count($this->requests);
    }
}
