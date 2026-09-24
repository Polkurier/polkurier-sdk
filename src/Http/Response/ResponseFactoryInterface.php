<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Parses an HTTP response into the SDK's response wrapper.
 */
interface ResponseFactoryInterface
{
    /**
     * @param PsrResponseInterface $httpResponse
     * @param string $responseFormat
     *
     * @return ResponseWrapper
     */
    public function create(PsrResponseInterface $httpResponse, string $responseFormat): ResponseWrapper;
}
