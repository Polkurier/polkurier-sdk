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
 * Pairs the parsed payload with the HTTP response it came from.
 */
final readonly class ResponseWrapper
{
    /**
     * @param ResponseInterface $httpResponse
     * @param ResponseMessage $parsedResponse
     * @param list<ResponseDrift> $drift how the payload differed from what the SDK expects
     */
    public function __construct(
        private ResponseInterface $httpResponse,
        private ResponseMessage $parsedResponse,
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
     * @return ResponseMessage
     */
    public function getParsedResponse(): ResponseMessage
    {
        return $this->parsedResponse;
    }
}
