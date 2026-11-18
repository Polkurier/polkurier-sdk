<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Psr\Http\Message\ResponseInterface;

/**
 * Pairs the envelope read from a response, its status and payload, with the HTTP response it came
 * from.
 */
final readonly class ResponseWrapper
{
    /**
     * @param ResponseInterface $httpResponse
     * @param ResponseMessage|ErrorMessage $envelope
     * @param list<ResponseDrift> $drift how the payload differed from what the SDK expects
     */
    public function __construct(
        private ResponseInterface $httpResponse,
        private ResponseMessage|ErrorMessage $envelope,
        private array $drift = [],
    ) {}

    /**
     * @return list<ResponseDrift>
     */
    public function getDrift(): array
    {
        return $this->drift;
    }

    /**
     * @return ResponseInterface
     */
    public function getHttpResponse(): ResponseInterface
    {
        return $this->httpResponse;
    }

    /**
     * @return ResponseMessage|ErrorMessage
     */
    public function getEnvelope(): ResponseMessage|ErrorMessage
    {
        return $this->envelope;
    }
}
