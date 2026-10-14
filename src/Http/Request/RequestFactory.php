<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Request;

use GuzzleHttp\Psr7\Request;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\SerializerInterface;
use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Builds requests in the format the API accepts.
 *
 * Every call is a POST to the root path with `apimethod`, `authorization` and `data` in the body.
 * The method is a body field, so there is only one endpoint. The `User-Agent` header names your
 * platform and version, followed by the SDK's.
 */
readonly class RequestFactory implements RequestFactoryInterface
{
    protected const string HTTP_METHOD = 'POST';
    protected const string ENDPOINT = '/';
    protected const string SDK_PRODUCT = 'polkurier-sdk/2.0.1';

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @param SerializerProviderInterface $serializerProvider
     * @param ConfigProviderInterface $configProvider read on every create(), so each request
     *        carries the configuration of the current call
     */
    public function __construct(
        SerializerProviderInterface $serializerProvider,
        protected ConfigProviderInterface $configProvider,
    ) {
        $this->serializer = $serializerProvider->getSerializer();
    }

    /**
     * @param ApiMethodInterface $apiMethod
     * @param MessageInterface|null $payload
     * @param AuthorizationMessage|bool|null $authorization credentials to send. null uses the
     *        configured ones, false sends none (for methods such as `heartbeat` that work without
     *        authentication), and a message overrides the configuration
     *
     * @return RequestInterface
     * @throws InvalidArgumentException when the payload cannot be serialized
     */
    public function create(
        ApiMethodInterface $apiMethod,
        ?MessageInterface $payload = null,
        AuthorizationMessage|bool|null $authorization = null,
    ): RequestInterface {
        $config = $this->configProvider->getConfig();
        $requestMessage = new RequestMessage(
            $apiMethod,
            $this->resolveAuthorization($authorization, $config),
            $payload,
        );

        try {
            $body = $this->serializer->serialize($requestMessage, Config::SERIALIZATION_FORMAT);
        } catch (SerializerException $e) {
            // A payload the serializer can't write is a caller error. Rethrow it as an SDK
            // exception.
            throw new InvalidArgumentException(
                \sprintf('The payload for %s could not be serialized: %s', $apiMethod->value, $e->getMessage()),
                0,
                $e,
            );
        }

        return new Request(
            method: static::HTTP_METHOD,
            uri: static::ENDPOINT,
            headers: ['User-Agent' => $this->userAgent($config)],
            body: $body,
        );
    }

    /**
     * `<platform>/<version> polkurier-sdk/<version>`.
     *
     * @param ConfigInterface $config
     *
     * @return string
     */
    protected function userAgent(ConfigInterface $config): string
    {
        return \sprintf(
            '%s/%s %s',
            $config->getPlatform(),
            $config->getPlatformVersion(),
            static::SDK_PRODUCT,
        );
    }

    /**
     * Converts the authorization argument into what
     * {@see \Polkurier\Webservice\Http\Request\RequestMessage} accepts.
     *
     * `null` means "use the configured credentials" and `false` means "send none". The request
     * message takes no bool, so `false` becomes null here.
     *
     * @param AuthorizationMessage|bool|null $authorization
     * @param ConfigInterface $config
     *
     * @return AuthorizationMessage|null
     */
    protected function resolveAuthorization(
        AuthorizationMessage|bool|null $authorization,
        ConfigInterface $config,
    ): ?AuthorizationMessage {
        return match (true) {
            $authorization instanceof AuthorizationMessage => $authorization,
            $authorization === false => null,
            default => $config->getAuthorization(),
        };
    }
}
