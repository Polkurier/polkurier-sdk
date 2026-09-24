<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Request;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Turns a method, a payload and a choice of credentials into a PSR-7 request.
 */
interface RequestFactoryInterface
{
    /**
     * @param ApiMethodInterface $apiMethod
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return RequestInterface
     */
    public function create(
        ApiMethodInterface $apiMethod,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null
    ): RequestInterface;
}
