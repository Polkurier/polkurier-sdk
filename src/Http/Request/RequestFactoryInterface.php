<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Request;

use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Psr\Http\Message\RequestInterface;

/**
 * Builds a PSR-7 request from an API method, a payload and the credentials to use.
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
