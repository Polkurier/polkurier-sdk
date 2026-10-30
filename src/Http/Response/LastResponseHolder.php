<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Serializer\ResponseDrift;
use Psr\Http\Message\ResponseInterface;

/**
 * Keeps the HTTP response to the last call, and how it differed from what the SDK expects.
 *
 * The services the facade builds share one instance, so it reflects the last call made through any
 * of them.
 * Each call clears it before sending.
 */
class LastResponseHolder
{
    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $response = null;

    /**
     * @var list<ResponseDrift>
     */
    protected array $drift = [];

    /**
     * Forgets the previous call.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->response = null;
        $this->drift = [];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * @param list<ResponseDrift> $drift
     *
     * @return void
     */
    public function setDrift(array $drift): void
    {
        $this->drift = $drift;
    }

    /**
     * @return ResponseInterface|null null if the last call got no response, e.g. the connection
     *         failed or the payload didn't pass validation
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return list<ResponseDrift>
     */
    public function getDrift(): array
    {
        return $this->drift;
    }
}
