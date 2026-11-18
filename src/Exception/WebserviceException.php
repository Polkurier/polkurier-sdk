<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @api
 */
class WebserviceException extends Exception
{
    /**
     * @param ErrorMessage $errorResponse
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        /**
         * @var ErrorMessage
         */
        private readonly ErrorMessage $errorResponse,

        /**
         * @var RequestInterface
         */
        private readonly RequestInterface $request,

        /**
         * @var ResponseInterface
         */
        private readonly ResponseInterface $response,
    ) {
        parent::__construct(
            \sprintf(
                'Polkurier API error: "%s", Error code: "%s", Status code: %s',
                (string)$errorResponse->response,
                $errorResponse->errorCode ?? '-',
                $errorResponse->statusCode ?? '-',
            ),
            $errorResponse->statusCode ?? 0,
        );
    }

    /**
     * @return ErrorMessage
     */
    public function getErrorMessage(): ErrorMessage
    {
        return $this->errorResponse;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
