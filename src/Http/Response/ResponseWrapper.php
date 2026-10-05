<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;


use Polkurier\Webservice\Serializer\UnknownEnumValue;
use Psr\Http\Message\ResponseInterface;

/**
 * Pairs the parsed payload with the HTTP response it came from.
 */
final readonly class ResponseWrapper
{
    /**
     * @param ResponseInterface $httpResponse
     * @param ResponseMessage $parsedResponse
     * @param list<UnknownEnumValue> $unknownEnumValues values the SDK read as null, or left out of
     *        a list, because their enum doesn't have them
     */
    public function __construct(
        private ResponseInterface $httpResponse,
        private ResponseMessage $parsedResponse,
        private array $unknownEnumValues = [],
    ) {}

    /**
     * @return list<UnknownEnumValue>
     */
    public function getUnknownEnumValues(): array
    {
        return $this->unknownEnumValues;
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
