<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\Http\ApiMethodInterface;
use Polkurier\Webservice\Api\Http\ClientFactoryInterface;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\SerializerInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Http\Request\RequestFactoryInterface;
use Polkurier\Webservice\Http\Response\LastResponseHolder;
use Polkurier\Webservice\Http\Response\ResponseFactoryInterface;
use Polkurier\Webservice\Http\Response\ResponseWrapper;
use Polkurier\Webservice\Log\LogSanitizer;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface as HttpMessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Shared request handling for all services.
 *
 * Builds the request, sends it, parses the reply, logs according to the configuration, and turns
 * an API error into the matching exception. Subclasses only supply the method, the type to
 * deserialize into and the payload.
 *
 * Logging: request and response bodies are prepared by the {@see LogSanitizer} first, which hides
 * secrets and personal data and takes files out unless the configuration allows them. Extended
 * logging is enabled per method, while error logging follows the basic logging flag.
 */
abstract class AbstractService
{
    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $lastHttpResponse = null;

    /**
     * @var ValidatorInterface
     */
    protected readonly ValidatorInterface $validator;

    /**
     * @param RequestFactoryInterface $requestFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param LoggerInterface $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ConfigProviderInterface $configProvider
     * @param ValidatorInterface|null $validator checks payloads before they are sent. Defaults to
     *        {@see DefaultValidatorRegistryFactory::create()}, as the facade does
     * @param LastResponseHolder $lastResponse receives the response to each call. Share one between
     *        services to see the last call made through any of them
     * @param LogSanitizer $logSanitizer prepares bodies for the log: takes files out and hides
     *        secrets and personal data, as the configuration says
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

        /**
         * @var ClientFactoryInterface
         */
        protected readonly ClientFactoryInterface $clientFactory,

        /**
         * @var ConfigProviderInterface
         */
        protected readonly ConfigProviderInterface $configProvider,

        ?ValidatorInterface $validator = null,

        /**
         * @var LastResponseHolder
         */
        protected readonly LastResponseHolder $lastResponse = new LastResponseHolder(),

        /**
         * @var LogSanitizer
         */
        protected readonly LogSanitizer $logSanitizer = new LogSanitizer(),
    ) {
        $this->validator = $validator ?? DefaultValidatorRegistryFactory::create();
    }

    /**
     * The configuration for the current call. It is read from the provider each time, so a
     * provider can return different settings from one call to the next.
     *
     * @return ConfigInterface
     */
    protected function config(): ConfigInterface
    {
        return $this->configProvider->getConfig();
    }

    /**
     * The HTTP client for the current call, from the client factory.
     *
     * @return ClientInterface
     */
    protected function client(): ClientInterface
    {
        return $this->clientFactory->create();
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param string $responseFormat
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return mixed the deserialized payload, in the shape $responseFormat asks for
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
        $this->lastResponse->clear();

        if ($payload !== null && $this->config()->validatePayloads()) {
            $this->validator->validate($payload);
        }

        $request = $this->requestFactory->create($apiMethod, $payload, $authorization);
        $this->logRequest($apiMethod, $request, $payload);

        try {
            $response = $this->client()->sendRequest($request);
            $this->lastResponse->setResponse($response);
            $wrapper = $this->responseFactory->create($response, $responseFormat);
            $this->lastResponse->setDrift($wrapper->getDrift());
            $this->logResponse(
                $apiMethod,
                $wrapper,
                $request,
                $payload,
                $wrapper->getEnvelope()->status === ResponseStatusEnum::Error
            );
            $this->logResponseDrift($apiMethod, $wrapper);
        } catch (ClientExceptionInterface $e) {
            $this->logClientError($e, $apiMethod, $request, $payload);

            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (MalformedResponseException $e) {
            $this->logMalformedResponse($e, $apiMethod, $request, $payload);

            throw $e;
        }

        $envelope = $wrapper->getEnvelope();
        if ($envelope instanceof ErrorMessage) {
            throw $this->buildWebserviceException($envelope, $request, $response);
        }

        $this->lastHttpResponse = $response;

        return $envelope->response;
    }

    /**
     * Returns the payload, which must be the object the endpoint promises.
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
     * @throws MalformedResponseException when the API returns something else
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
     * Returns the payload for an endpoint that may return nothing.
     *
     * Some endpoints say "not found" with an empty array instead of an error, and the response
     * factory turns that into null. Everywhere else a missing object is an error.
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
     * Returns the payload for an endpoint that returns a list. An empty list is valid, and an entry
     * sent as an empty object is left out.
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

        // An entry sent as an empty object reads as null. It carries nothing, so it is left out
        // rather than failing the whole list.
        $value = \array_filter($value, static fn(mixed $item): bool => $item !== null);

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
     * Returns the payload for an endpoint that returns a map of plain strings, such as codes to
     * names.
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
            SerializerInterface::FORMAT_STRING_LIST,
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
     * Returns the payload as decoded JSON, for a response with no fixed structure.
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
            SerializerInterface::FORMAT_RAW_ARRAY,
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
     * Returns the payload for an endpoint that only acknowledges.
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
            SerializerInterface::FORMAT_BOOLEAN,
            $payload,
            $authorization,
        ) !== false;
    }

    /**
     * The HTTP response from this service's most recent call.
     *
     * Used by the exception factories to report what arrived. For a failed call, the response is
     * on the exception. This is always the response that was just parsed.
     *
     * @return ResponseInterface
     * @throws InvalidArgumentException when no request has been made yet
     */
    protected function lastHttpResponse(): ResponseInterface
    {
        if ($this->lastHttpResponse === null) {
            // An SDK exception instead of \LogicException, so every exception the SDK throws
            // extends the SDK's base exception.
            throw new InvalidArgumentException('No request has been made yet, so there is no response to report.');
        }

        return $this->lastHttpResponse;
    }

    /**
     * Picks the exception class for an API error.
     *
     * An error response can lack either `status_code` or `error_code`, so the check uses whichever
     * one is present.
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
        $statusCode = $errorMessage->statusCode;
        $errorCode = $errorMessage->errorCode;

        $isAuthorizationError =
            ($statusCode !== null && AuthorizationException::isAuthorizationErrorStatus($statusCode))
            || ($errorCode !== null && AuthorizationException::isAuthorizationErrorCode($errorCode));

        return $isAuthorizationError
            ? new AuthorizationException($errorMessage, $request, $response)
            : new WebserviceException($errorMessage, $request, $response);
    }

    /**
     * The body of a request or response as it goes into a log message: with file contents taken
     * out, and secrets and personal data hidden unless the configuration allows them.
     *
     * @param HttpMessageInterface $message
     * @param ApiMethodInterface $apiMethod
     *
     * @return string
     */
    protected function bodyForLog(HttpMessageInterface $message, ApiMethodInterface $apiMethod): string
    {
        return $this->logSanitizer->body($message, $this->config(), $apiMethod);
    }

    /**
     * The log context, prepared the same way as the bodies.
     *
     * The request and response are replaced with copies whose bodies are prepared for the log. The
     * payload and parsed response are objects that can't be prepared, so they are left out unless
     * the configuration allows logging both secrets and personal data. Their contents are in the
     * request and response bodies either way.
     *
     * @param array<string, mixed> $context
     * @param ApiMethodInterface $apiMethod
     *
     * @return array<string, mixed>
     */
    protected function contextForLog(array $context, ApiMethodInterface $apiMethod): array
    {
        if (!$this->logSanitizer->allowsObjects($this->config())) {
            unset($context['payload'], $context['parsed_response']);
        }
        foreach (['request', 'response'] as $key) {
            if (($context[$key] ?? null) instanceof HttpMessageInterface) {
                $context[$key] = $this->logSanitizer->message($context[$key], $this->config(), $apiMethod);
            }
        }

        return $context;
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param RequestInterface $request
     * @param MessageInterface|null $payload
     *
     * @return void
     */
    protected function logRequest(
        ApiMethodInterface $apiMethod,
        RequestInterface $request,
        ?MessageInterface $payload,
    ): void {
        if (!$this->config()->isExtendedLoggingEnabled($apiMethod)) {
            return;
        }
        $this->logger->info(\sprintf(
            'Request method: "%s", payload: "%s"',
            $apiMethod->value,
            $this->bodyForLog($request, $apiMethod),
        ), $this->contextForLog([
            'method' => $apiMethod,
            'sandbox' => $this->config()->isSandbox(),
            'payload' => $payload,
            'request' => $request,
        ], $apiMethod));
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
        if ($error ? !$this->config()->isLoggingEnabled() : !$this->config()->isExtendedLoggingEnabled($apiMethod)) {
            return;
        }
        $this->logger->log(
            $error ? LogLevel::ERROR : LogLevel::INFO,
            sprintf(
                'Response from method: "%s", response: "%s"',
                $apiMethod->value,
                $this->bodyForLog($responseWrapper->getHttpResponse(), $apiMethod),
            ),
            $this->contextForLog([
                'method' => $apiMethod,
                'sandbox' => $this->config()->isSandbox(),
                'payload' => $payload,
                'request' => $request,
                'parsed_response' => $responseWrapper->getEnvelope(),
                'response' => $responseWrapper->getHttpResponse(),
            ], $apiMethod)
        );
    }

    /**
     * Logs a warning for each way the response differed from what the SDK expects, if logging is
     * enabled.
     *
     * The SDK reads the response anyway, so the call still succeeds. The warning says what differed
     * and where in the response.
     *
     * @param ApiMethodInterface $apiMethod
     * @param ResponseWrapper $responseWrapper
     *
     * @return void
     */
    protected function logResponseDrift(
        ApiMethodInterface $apiMethod,
        ResponseWrapper $responseWrapper,
    ): void {
        if (!$this->config()->isLoggingEnabled()) {
            return;
        }

        foreach ($responseWrapper->getDrift() as $drift) {
            $value = $drift->value;
            $shown = \is_string($value) ? '"' . $value . '"' : \get_debug_type($value);
            $this->logger->warning(\sprintf(
                match ($drift->type) {
                    ResponseDriftTypeEnum::UnknownField => 'Unknown field "%1$s" in the response from method: "%4$s".'
                        . ' The SDK ignores it.',
                    ResponseDriftTypeEnum::MissingField => 'Field "%1$s" (%3$s) is missing or null in the response'
                        . ' from method: "%4$s". The SDK reads it as its default or empty value.',
                    ResponseDriftTypeEnum::CastValue => 'Field "%1$s" (%3$s) is %2$s in the response from method:'
                        . ' "%4$s". The SDK converts it, or reads it as its default or empty value.',
                    ResponseDriftTypeEnum::UnknownEnumValue => 'Unknown %3$s value %2$s at "%1$s" in the response'
                        . ' from method: "%4$s". The SDK reads it as null, or leaves it out of a list.',
                },
                $drift->path,
                $shown,
                $drift->expected ?? '',
                $apiMethod->value,
            ), [
                'method' => $apiMethod,
                'sandbox' => $this->config()->isSandbox(),
                'type' => $drift->type,
                'path' => $drift->path,
                'value' => $value,
                'expected' => $drift->expected,
            ]);
        }
    }

    /**
     * Logs a response that couldn't be read, if logging is enabled.
     *
     * @param MalformedResponseException $exception
     * @param ApiMethodInterface $apiMethod
     * @param RequestInterface $request
     * @param MessageInterface|null $payload
     *
     * @return void
     */
    protected function logMalformedResponse(
        MalformedResponseException $exception,
        ApiMethodInterface $apiMethod,
        RequestInterface $request,
        ?MessageInterface $payload
    ): void {
        if (!$this->config()->isLoggingEnabled()) {
            return;
        }
        $this->logger->error(\sprintf(
            'Malformed response: "%s", while requesting method: "%s"',
            $exception->getMessage(),
            $apiMethod->value,
        ), $this->contextForLog([
            'method' => $apiMethod,
            'sandbox' => $this->config()->isSandbox(),
            'payload' => $payload,
            'request' => $request,
            'exception' => $exception,
        ], $apiMethod));
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
        if (!$this->config()->isLoggingEnabled()) {
            return;
        }
        $this->logger->error(\sprintf(
            'HTTP client error: "%s", while requesting method: "%s" with payload: "%s"',
            $exception->getMessage(),
            $apiMethod->value,
            $this->bodyForLog($request, $apiMethod),
        ), $this->contextForLog([
            'method' => $apiMethod,
            'sandbox' => $this->config()->isSandbox(),
            'payload' => $payload,
            'request' => $request,
            'client_exception' => $exception,
        ], $apiMethod));
    }
}
