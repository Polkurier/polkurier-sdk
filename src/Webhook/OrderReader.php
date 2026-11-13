<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Webhook;

use Polkurier\Webservice\Api\Webhook\OrderReaderInterface;
use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Reads order events as {@see OrderReaderInterface} describes.
 *
 * @api
 */
class OrderReader implements OrderReaderInterface
{
    /**
     * @var list<ResponseDrift>
     */
    protected array $lastDrift = [];

    /**
     * @var SerializerProviderInterface
     */
    protected readonly SerializerProviderInterface $serializerProvider;

    /**
     * @param SerializerConfig|null $serializerConfig how the order in an event is read. Defaults to
     *        the SDK's own settings
     */
    public function __construct(?SerializerConfig $serializerConfig = null)
    {
        $this->serializerProvider = new SerializerProvider($serializerConfig ?? new SerializerConfig());
    }

    /**
     * Whether the event was signed with the webhook's secret.
     *
     * The hash is an HMAC-SHA256 of the event's other fields, form-encoded, keyed with the secret.
     *
     * @param string $body the request body, as it came
     * @param string $secret the secret the Polkurier panel gives for the webhook
     *
     * @return bool false also when the body has no hash, or the secret is empty
     */
    public function isAuthentic(string $body, string $secret): bool
    {
        $fields = $this->decode($body);
        $hash = $fields[OrderEventMessage::FIELD_HASH] ?? null;
        if ($secret === '' || !\is_string($hash) || $hash === '') {
            return false;
        }
        unset($fields[OrderEventMessage::FIELD_HASH]);

        return \hash_equals(\hash_hmac('sha256', \http_build_query($fields), $secret), \strtolower(\trim($hash)));
    }

    /**
     * Reads the event. It doesn't check the hash, see {@see self::isAuthentic()}.
     *
     * @param string $body the request body, as it came
     *
     * @return OrderEventMessage
     * @throws InvalidArgumentException when the body isn't an order event
     */
    public function read(string $body): OrderEventMessage
    {
        $this->lastDrift = [];
        $fields = $this->decode($body);
        if (!isset($fields[OrderEventMessage::FIELD_TYPE])
            || !\is_array($fields[OrderEventMessage::FIELD_DATA] ?? null)
        ) {
            throw new InvalidArgumentException('The body is not a Polkurier order event: it has no type or no order.');
        }

        $drift = new ResponseDriftCollector();

        try {
            // Form fields are all strings. For a text format such as CSV, the serializer casts a
            // string to the scalar type the property declares. A form leaves out null values and
            // empty lists, so a field that can be null may be absent, and an order without waybills
            // arrives without the field. An order that differs from what the SDK expects is read as
            // in a response, see self::getLastDrift().
            $message = $this->serializerProvider->getSerializer()->denormalize(
                $fields,
                OrderEventMessage::class,
                CsvEncoder::FORMAT,
                [
                    AbstractNormalizer::REQUIRE_ALL_PROPERTIES => false,
                    AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [Order::class => ['waybills' => []]],
                    ResponseDriftCollector::CONTEXT_KEY => $drift,
                ],
            );
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException(
                'The order in the Polkurier order event could not be read: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!$message instanceof OrderEventMessage) {
            throw new InvalidArgumentException('The body is not a Polkurier order event.');
        }
        $this->lastDrift = $drift->all();

        return $message;
    }

    /**
     * How the order in the last event read differed from what this version of the SDK expects.
     *
     * The event is read anyway, as {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum}
     * describes.
     *
     * @return list<ResponseDrift> empty when the order was as expected, or nothing was read
     */
    public function getLastDrift(): array
    {
        return $this->lastDrift;
    }

    /**
     * Decodes a form-encoded body the way PHP fills `$_POST`, nested names included, without its
     * `max_input_vars` limit.
     *
     * @param string $body
     *
     * @return array<array-key, mixed>
     */
    protected function decode(string $body): array
    {
        $fields = [];

        foreach (\explode('&', $body) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$name, $value] = \array_pad(\explode('=', $pair, 2), 2, '');
            $name = \urldecode($name);
            $value = \urldecode($value);

            $open = \strpos($name, '[');
            if ($open === false || $open === 0) {
                $path = [$name];
            } else {
                \preg_match_all('/\[([^\]]*)\]/', \substr($name, $open), $matches);
                $path = [\substr($name, 0, $open), ...$matches[1]];
            }

            $fields = self::place($fields, $path, $value);
        }

        return $fields;
    }

    /**
     * @param array<array-key, mixed> $fields
     * @param list<string> $path
     * @param string $value
     *
     * @return array<array-key, mixed>
     */
    private static function place(array $fields, array $path, string $value): array
    {
        $key = \array_shift($path);

        if ($path === []) {
            if ($key === '') {
                $fields[] = $value;
            } else {
                $fields[$key] = $value;
            }

            return $fields;
        }

        if ($key === '') {
            $fields[] = self::place([], $path, $value);

            return $fields;
        }

        $inner = $fields[$key] ?? [];
        $fields[$key] = self::place(\is_array($inner) ? $inner : [], $path, $value);

        return $fields;
    }
}
