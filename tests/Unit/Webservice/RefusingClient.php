<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A client whose every request fails at the transport level.
 */
final class RefusingClient implements ClientInterface, ClientFactoryInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new class ('Connection refused') extends \RuntimeException implements ClientExceptionInterface {};
    }

    public function create(): ClientInterface
    {
        return $this;
    }
}
