<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Testing;

use GuzzleHttp\Psr7\Response;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An HTTP client that returns a fixed body and records the requests it receives.
 *
 * It tests the service layer end to end (request building, serialization, response parsing)
 * without a network. It is also its own factory, so it can be passed straight to a service
 * constructor.
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
        private readonly int $status = 200,
    ) {}

    /**
     * @param string $body
     *
     * @return void
     */
    public function willAnswerWith(string $body): void
    {
        $this->body = $body;
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
    public function lastRequest(): ?RequestInterface
    {
        return $this->requests[\count($this->requests) - 1] ?? null;
    }

    /**
     * The decoded request body of the most recent call.
     *
     * @return array<string, mixed>
     */
    public function lastPayload(): array
    {
        $request = $this->lastRequest();
        if ($request === null) {
            return [];
        }
        /** @var array<string, mixed> $decoded */
        $decoded = \json_decode((string)$request->getBody(), true) ?? [];

        return $decoded;
    }

    /**
     * @return int
     * @phpstan-impure
     */
    public function requestCount(): int
    {
        return \count($this->requests);
    }
}
