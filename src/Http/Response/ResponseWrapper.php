<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;


use Psr\Http\Message\ResponseInterface;

/**
 * Pairs the parsed payload with the HTTP response it came from.
 */
final readonly class ResponseWrapper
{
    /**
     * @param ResponseInterface $httpResponse
     * @param ResponseMessage $parsedResponse
     */
    public function __construct(
        private ResponseInterface $httpResponse,
        private ResponseMessage $parsedResponse,
    ) {}

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
