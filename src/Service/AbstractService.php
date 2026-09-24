<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Monolog\Level;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\ClientFactoryInterface;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\ErrorMessage;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Validator\NullValidator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Shared request pipeline for every service.
 *
 * Builds the request, sends it, parses the reply, logs according to configuration, and converts an
 * API error into the matching exception. Subclasses supply only the method, the type to
 * deserialize into, and the payload.
 *
 * On logging: request and response bodies are logged verbatim, and a request body contains the API
 * token. Configure a logger accordingly, and note that extended logging is opt-in per method while
 * error logging follows the base logging flag.
 */
abstract class AbstractService
{
    /**
     * @var ClientInterface
     */
    protected ClientInterface $client;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $lastHttpResponse = null;

    /**
     * @param RequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param LoggerInterface $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ConfigProviderInterface $configProvider
     * @param ValidatorInterface $validator checks payloads before they are sent; defaults to one
     *        that accepts everything, so a service constructed by hand behaves as it did before
     *        validation existed
     */
    public function __construct(
        /**
         * @var RequestFactoryInterface
         */
        protected readonly RequestFactoryInterface $requestFactory,

        /**
         * @var ResponseFactoryInterface
         */
        protected readonly ResponseFactoryInterface $responseFactory,

        /**
         * @var LoggerInterface
         */
        protected readonly LoggerInterface $logger,

        ClientFactoryInterface $clientFactory,
        ConfigProviderInterface $configProvider,

        /**
         * @var ValidatorInterface
         */
        protected readonly ValidatorInterface $validator = new NullValidator(),
    ) {
        $this->client = $clientFactory->create();
        $this->config = $configProvider->getConfig();
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param string $responseFormat
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return mixed the deserialized payload, in whatever shape $responseFormat asked for
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException when the payload is rejected before anything is sent
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function processRequest(
        ApiMethodInterface $apiMethod,
        string $responseFormat,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): mixed {
        if ($payload !== null && $this->config->validatePayloads()) {
            $this->validator->validate($payload);
        }

        $request = $this->requestFactory->create($apiMethod, $payload, $authorization);
        $this->logRequest($apiMethod, $request, $payload);

        try {
            $response = $this->client->sendRequest($request);
            $wrapper = $this->responseFactory->create($response, $responseFormat);
            $this->logResponse(
                $apiMethod,
                $wrapper,
                $request,
                $payload,
                $wrapper->getParsedResponse()->getStatus() === ResponseStatusEnum::Error
            );
        } catch (ClientExceptionInterface $e) {
            $this->logClientError($e, $apiMethod, $request, $payload);

            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        $parsedResponse = $wrapper->getParsedResponse();
        if ($parsedResponse instanceof ErrorMessage) {
            throw $this->buildWebserviceException($parsedResponse, $request, $response);
        }

        $this->lastHttpResponse = $response;

        return $parsedResponse->getResponse();
    }

    /**
     * The payload, insisting it is the object the endpoint promises.
     *
     * @template T of object
     *
     * @param ApiMethodInterface $apiMethod
     * @param class-string<T> $responseClass
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return T
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException when the API answers with something else
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestObject(
        ApiMethodInterface $apiMethod,
        string $responseClass,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): object {
        $value = $this->processRequest($apiMethod, $responseClass, $payload, $authorization);

        if (!$value instanceof $responseClass) {
            throw MalformedResponseException::unexpectedPayload(
                $this->lastHttpResponse(),
                $apiMethod->value,
                $responseClass,
                $value,
            );
        }

        return $value;
    }

    /**
     * The payload for an endpoint that answers with nothing when it has nothing to answer with.
     *
     * The API says "not found" by sending an empty array rather than an error, which the response
     * factory turns into null. Only the endpoints that genuinely do this should use this method;
     * everywhere else a missing object is a broken promise, not an absence.
     *
     * @template T of object
     *
     * @param ApiMethodInterface $apiMethod
     * @param class-string<T> $responseClass
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return T|null
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestNullableObject(
        ApiMethodInterface $apiMethod,
        string $responseClass,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): ?object {
        $value = $this->processRequest($apiMethod, $responseClass, $payload, $authorization);

        if ($value === null || $value instanceof $responseClass) {
            return $value;
        }

        throw MalformedResponseException::unexpectedPayload(
            $this->lastHttpResponse(),
            $apiMethod->value,
            $responseClass,
            $value,
        );
    }

    /**
     * The payload for an endpoint that answers with a list. An empty list is a valid answer.
     *
     * @template T of object
     *
     * @param ApiMethodInterface $apiMethod
     * @param class-string<T> $itemClass
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return list<T>
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestList(
        ApiMethodInterface $apiMethod,
        string $itemClass,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): array {
        $value = $this->processRequest(
            $apiMethod,
            $itemClass . '[]',
            $payload,
            $authorization,
        );

        if (!\is_array($value)) {
            throw MalformedResponseException::unexpectedPayload(
                $this->lastHttpResponse(),
                $apiMethod->value,
                $itemClass . '[]',
                $value,
            );
        }

        foreach ($value as $item) {
            if (!$item instanceof $itemClass) {
                throw MalformedResponseException::unexpectedPayload(
                    $this->lastHttpResponse(),
                    $apiMethod->value,
                    $itemClass . '[]',
                    $item,
                );
            }
        }

        return \array_values($value);
    }

    /**
     * The payload for an endpoint that answers with a map of plain strings, such as a code to a
     * name.
     *
     * @param ApiMethodInterface $apiMethod
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return array<string, string>
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestStringMap(
        ApiMethodInterface $apiMethod,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): array {
        $value = $this->processRequest(
            $apiMethod,
            ResponseFactory::FORMAT_STRING_LIST,
            $payload,
            $authorization,
        );

        if (!\is_array($value)) {
            throw MalformedResponseException::unexpectedPayload(
                $this->lastHttpResponse(),
                $apiMethod->value,
                'array<string, string>',
                $value,
            );
        }

        $map = [];
        foreach ($value as $key => $item) {
            $map[(string)$key] = \is_scalar($item) ? (string)$item : '';
        }

        return $map;
    }

    /**
     * The payload for an endpoint whose shape varies with what was asked for.
     *
     * A last resort, for the two endpoints whose answer has no stable structure to map onto. The
     * result is decoded JSON and nothing more.
     *
     * @param ApiMethodInterface $apiMethod
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return array<array-key, mixed>
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestRawArray(
        ApiMethodInterface $apiMethod,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): array {
        $value = $this->processRequest(
            $apiMethod,
            ResponseFactory::FORMAT_RAW_ARRAY,
            $payload,
            $authorization,
        );

        if (!\is_array($value)) {
            throw MalformedResponseException::unexpectedPayload(
                $this->lastHttpResponse(),
                $apiMethod->value,
                'array',
                $value,
            );
        }

        return $value;
    }

    /**
     * The payload for an endpoint that only acknowledges.
     *
     * @param ApiMethodInterface $apiMethod
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     * @throws InvalidArgumentException
     */
    protected function requestAcknowledgement(
        ApiMethodInterface $apiMethod,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): bool {
        return $this->processRequest(
            $apiMethod,
            ResponseFactory::FORMAT_BOOLEAN,
            $payload,
            $authorization,
        ) !== false;
    }

    /**
     * The HTTP response behind the most recent call this service made.
     *
     * Kept for the exception factories, which report what arrived. On a failed call the response
     * is on the exception itself; this is only ever the one just parsed.
     *
     * @return ResponseInterface
     * @throws InvalidArgumentException when no request has been made yet
     */
    protected function lastHttpResponse(): ResponseInterface
    {
        if ($this->lastHttpResponse === null) {
            // The SDK's own hierarchy rather than \LogicException, so that everything a caller
            // can see leaving the SDK extends Polkurier's base exception.
            throw new InvalidArgumentException('No request has been made yet, so there is no response to report.');
        }

        return $this->lastHttpResponse;
    }

    /**
     * Picks the exception class for an API error.
     *
     * Either `status_code` or `error_code` may be absent from an error response, so neither can be
     * dereferenced unconditionally and the classification has to work from whichever arrived.
     *
     * @param ErrorMessage $errorMessage
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return WebserviceException
     */
    protected function buildWebserviceException(
        ErrorMessage $errorMessage,
        RequestInterface $request,
        ResponseInterface $response,
    ): WebserviceException {
        $statusCode = $errorMessage->getStatusCode();
        $errorCode = $errorMessage->getErrorCode();

        $isAuthorizationError =
            ($statusCode !== null && AuthorizationException::isAuthorizationErrorStatus($statusCode))
            || ($errorCode !== null && AuthorizationException::isAuthorizationErrorCode($errorCode));

        return $isAuthorizationError
            ? new AuthorizationException($errorMessage, $request, $response)
            : new WebserviceException($errorMessage, $request, $response);
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param RequestInterface $request
     * @param MessageInterface|null $payload
     *
     * @return void
     */
    protected function logRequest(ApiMethodInterface $apiMethod, RequestInterface $request, ?MessageInterface $payload): void
    {
        if (!$this->config->isExtendedLoggingEnabled($apiMethod)) {
            return;
        }
        $this->logger->info(\sprintf(
            'Request method: "%s", payload: "%s"',
            $apiMethod->value,
            $request->getBody(),
        ), [
            'method' => $apiMethod,
            'sandbox' => $this->config->isSandbox(),
            'payload' => $payload,
            'request' => $request,
        ]);
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param ResponseWrapper $responseWrapper
     * @param RequestInterface $request
     * @param MessageInterface|null $payload
     * @param bool $error
     *
     * @return void
     */
    protected function logResponse(
        ApiMethodInterface $apiMethod,
        ResponseWrapper $responseWrapper,
        RequestInterface $request,
        ?MessageInterface $payload,
        bool $error = false,
    ): void {
        if ($error ? !$this->config->isLoggingEnabled() : !$this->config->isExtendedLoggingEnabled($apiMethod)) {
            return;
        }
        $this->logger->log(
            $error ? Level::Error : Level::Info,
            sprintf(
                'Response from method: "%s", response: "%s"',
                $apiMethod->value,
                $responseWrapper->getHttpResponse()->getBody(),
            ),
            [
                'method' => $apiMethod,
                'sandbox' => $this->config->isSandbox(),
                'payload' => $payload,
                'request' => $request,
                'parsed_response' => $responseWrapper->getParsedResponse(),
                'response' => $responseWrapper->getHttpResponse(),
            ]
        );
    }

    /**
     * @param \Throwable $exception
     * @param ApiMethodInterface $apiMethod
     * @param RequestInterface $request
     * @param MessageInterface|null $payload
     *
     * @return void
     */
    protected function logClientError(
        \Throwable $exception,
        ApiMethodInterface $apiMethod,
        RequestInterface $request,
        ?MessageInterface $payload
    ): void {
        if (!$this->config->isLoggingEnabled()) {
            return;
        }
        $this->logger->error(\sprintf(
            'HTTP client error: "%s", while requesting method: "%s" with payload: "%s"',
            $exception->getMessage(),
            $apiMethod->value,
            $request->getBody(),
        ), [
            'method' => $apiMethod,
            'sandbox' => $this->config->isSandbox(),
            'payload' => $payload,
            'request' => $request,
            'client_exception' => $exception,
        ]);
    }
}
