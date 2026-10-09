<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Serializer\UnknownEnumValue;
use Psr\Http\Message\ResponseInterface;

/**
 * Keeps the HTTP response to the last call, and the enum values in it that the SDK doesn't know.
 *
 * The services share one instance, so it always reflects the last call made through any of them.
 * Each call clears it before sending.
 */
class LastResponseHolder
{
    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $response = null;

    /**
     * @var list<UnknownEnumValue>
     */
    protected array $unknownEnumValues = [];

    /**
     * Forgets the previous call.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->response = null;
        $this->unknownEnumValues = [];
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
     * @param list<UnknownEnumValue> $unknownEnumValues
     *
     * @return void
     */
    public function setUnknownEnumValues(array $unknownEnumValues): void
    {
        $this->unknownEnumValues = $unknownEnumValues;
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
     * @return list<UnknownEnumValue>
     */
    public function getUnknownEnumValues(): array
    {
        return $this->unknownEnumValues;
    }
}
