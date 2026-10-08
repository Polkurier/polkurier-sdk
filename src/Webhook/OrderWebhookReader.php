<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Webhook;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Response\Webhook\OrderWebhookMessage;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Polkurier\Webservice\Serializer\UnknownEnumValues;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Reads the order events Polkurier posts to the webhook address set in the Polkurier panel.
 *
 * An event is a form-encoded POST with the fields `type`, `data` (the order) and `hash`. Check it
 * with {@see self::isAuthentic()} against the secret the panel gives for the webhook, then read it
 * with {@see self::read()}. Answer with HTTP 200 and {@see self::ACKNOWLEDGEMENT} as the body.
 *
 * Both take the request body as it came, e.g. `file_get_contents('php://input')`, rather than
 * `$_POST`. A large order has more fields than PHP's `max_input_vars` lets `$_POST` hold.
 *
 * Treat an event as a hint that the order changed. The same event can come more than once, so
 * handle it in a way that is safe to repeat, and read the order's current status from the order,
 * not only from the event's type.
 */
class OrderWebhookReader
{
    /**
     * The body to answer an event with, along with HTTP 200.
     */
    public const string ACKNOWLEDGEMENT = 'OK';

    /**
     * @param SerializerProviderInterface $serializerProvider
     */
    public function __construct(
        protected readonly SerializerProviderInterface $serializerProvider = new SerializerProvider(),
    ) {}

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
        $hash = $fields[OrderWebhookMessage::FIELD_HASH] ?? null;
        if ($secret === '' || !\is_string($hash) || $hash === '') {
            return false;
        }
        unset($fields[OrderWebhookMessage::FIELD_HASH]);

        return \hash_equals(\hash_hmac('sha256', \http_build_query($fields), $secret), \strtolower(\trim($hash)));
    }

    /**
     * Reads the event. It doesn't check the hash, see {@see self::isAuthentic()}.
     *
     * @param string $body the request body, as it came
     *
     * @return OrderWebhookMessage
     * @throws InvalidArgumentException when the body isn't an order event
     */
    public function read(string $body): OrderWebhookMessage
    {
        $fields = $this->decode($body);
        if (!isset($fields[OrderWebhookMessage::FIELD_TYPE]) || !\is_array($fields[OrderWebhookMessage::FIELD_DATA] ?? null)) {
            throw new InvalidArgumentException('The body is not a Polkurier order event: it has no type or no order.');
        }

        try {
            // Form fields are all strings. For a text format such as CSV, the serializer casts a
            // string to the scalar type the property declares. A form can't carry an empty list,
            // so an order without waybills arrives without the field. An enum value the SDK doesn't
            // know reads as null, as in a response.
            $message = $this->serializerProvider->getSerializer()->denormalize(
                $fields,
                OrderWebhookMessage::class,
                CsvEncoder::FORMAT,
                [
                    AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [Order::class => ['waybills' => []]],
                    UnknownEnumValues::CONTEXT_KEY => new UnknownEnumValues(),
                ],
            );
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException('The order in the Polkurier order event could not be read: ' . $e->getMessage(), 0, $e);
        }

        if (!$message instanceof OrderWebhookMessage) {
            throw new InvalidArgumentException('The body is not a Polkurier order event.');
        }

        return $message;
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
