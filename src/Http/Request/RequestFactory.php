<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
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
 * The method is a body field, so there is only one endpoint.
 */
readonly class RequestFactory implements RequestFactoryInterface
{
    protected const string HTTP_METHOD = 'POST';
    protected const string ENDPOINT = '/';

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param SerializerProviderInterface $serializerProvider
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        SerializerProviderInterface $serializerProvider,
        ConfigProviderInterface $configProvider,
    ) {
        $this->config = $configProvider->getConfig();
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
        $requestMessage = new RequestMessage($apiMethod, $this->resolveAuthorization($authorization), $payload);

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
            body: $body,
        );
    }

    /**
     * Converts the authorization argument into what RequestMessage accepts.
     *
     * `null` means "use the configured credentials" and `false` means "send none". RequestMessage
     * takes no bool, so `false` becomes null here.
     *
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return AuthorizationMessage|null
     */
    protected function resolveAuthorization(
        AuthorizationMessage|bool|null $authorization
    ): ?AuthorizationMessage {
        return match (true) {
            $authorization instanceof AuthorizationMessage => $authorization,
            $authorization === false => null,
            default => $this->config->getAuthorization(),
        };
    }
}
