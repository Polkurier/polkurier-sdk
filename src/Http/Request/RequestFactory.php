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
 * Builds the single request shape the API accepts.
 *
 * Every call is a POST to the root path carrying `apimethod`, `authorization` and `data`; the
 * method is a field of the body rather than a path, so there is only ever one endpoint.
 */
final readonly class RequestFactory implements RequestFactoryInterface
{
    private const string HTTP_METHOD = 'POST';
    private const string ENDPOINT = '/';

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

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
     * @param AuthorizationMessage|bool|null $authorization credentials to send: null uses
     *        the configured ones, false sends none (for methods such as `heartbeat` that must work
     *        unauthenticated), and an explicit message overrides the configuration
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
            // A payload the serializer cannot write is a fault in what was handed to it, and the
            // caller should see one of the SDK's own exceptions rather than the serializer's.
            throw new InvalidArgumentException(
                \sprintf('The payload for %s could not be serialized: %s', $apiMethod->value, $e->getMessage()),
                0,
                $e,
            );
        }

        return new Request(
            method: self::HTTP_METHOD,
            uri: self::ENDPOINT,
            body: $body,
        );
    }

    /**
     * Turns the three-way authorization argument into what RequestMessage accepts.
     *
     * `false` is not the same as `null`: null means "use the configured credentials", false means
     * "deliberately send none". Passing false straight through raised a TypeError, which broke
     * every unauthenticated method.
     *
     * @param AuthorizationMessage|bool|null $authorization
     *
     * @return AuthorizationMessage|null
     */
    private function resolveAuthorization(
        AuthorizationMessage|bool|null $authorization
    ): ?AuthorizationMessage {
        return match (true) {
            $authorization instanceof AuthorizationMessage => $authorization,
            $authorization === false => null,
            default => $this->config->getAuthorization(),
        };
    }
}
