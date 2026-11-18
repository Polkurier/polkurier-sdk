<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Webhook;

use Polkurier\Webservice\Api\SerializerConfigInterface;
use Polkurier\Webservice\Api\Webhook\OrderReaderInterface;
use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Reads order events as {@see OrderReaderInterface} describes.
 *
 * @api
 */
readonly class OrderReader implements OrderReaderInterface
{
    /**
     * @var SerializerProvider
     */
    protected SerializerProvider $serializerProvider;

    /**
     * @param SerializerConfigInterface|null $serializerConfig how the order in an event is read. Defaults to
     *        the SDK's own settings
     */
    public function __construct(?SerializerConfigInterface $serializerConfig = null)
    {
        $this->serializerProvider = new SerializerProvider($serializerConfig ?? new SerializerConfig());
    }

    /**
     * Whether the event was signed with the webhook's secret.
     *
     * The hash is an HMAC-SHA256 of the event's other fields, form-encoded, keyed with the secret.
     *
     * Polkurier builds the body with `http_build_query()` and appends `hash` last, so the hashed
     * string is the body up to `&hash=`. Decoding the fields and encoding them again reproduces
     * it: PHP's own encoding is what both sides use, and the event's top-level names carry
     * nothing `parse_str()` would rewrite.
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

        // The separator is given rather than left to `arg_separator.output`, which an installation
        // can set to something else and which would then make every genuine event look forged.
        return \hash_equals(
            \hash_hmac('sha256', \http_build_query($fields, '', '&'), $secret),
            \strtolower(\trim($hash)),
        );
    }

    /**
     * Reads the event. It doesn't check the hash, see {@see self::isAuthentic()}.
     *
     * @param string $body the request body, as it came
     *
     * @return ReadEvent
     * @throws InvalidArgumentException when the body isn't an order event
     * @throws \ArgumentCountError when the class the payload maps onto cannot be built from any
     *         payload, which is a defect in that class rather than in what arrived
     */
    public function read(string $body): ReadEvent
    {
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
            // arrives without the field. An order that differs from what the SDK expects is read
            // as in a response, and the differences come back with the event.
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
        } catch (\ArgumentCountError $e) {
            // The event class cannot be built from form fields at all, which is a defect in that
            // class rather than in what was posted.
            throw $e;
        } catch (SerializerException | \TypeError $e) {
            // A constructor refusing what the denormalizer built raises a TypeError, which is
            // outside the SDK's hierarchy just as the serializer's own exceptions are.
            throw new InvalidArgumentException(
                'The order in the Polkurier order event could not be read: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        if (!$message instanceof OrderEventMessage) {
            throw new InvalidArgumentException('The body is not a Polkurier order event.');
        }

        return new ReadEvent($message, $drift->getDrift());
    }

    /**
     * Decodes a form-encoded body into nested arrays, as PHP does for `$_POST` but without its
     * `max_input_vars` limit, which an order with many items exceeds.
     *
     * A key is kept as sent. PHP rewrites `.` and a space in a top-level name to `_`; an order
     * event carries no such name, and rewriting one would hide it from the field it belongs to.
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

            $fields = self::placeValue($fields, $path, $value);
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
    private static function placeValue(array $fields, array $path, string $value): array
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
            $fields[] = self::placeValue([], $path, $value);

            return $fields;
        }

        $inner = $fields[$key] ?? [];
        $fields[$key] = self::placeValue(\is_array($inner) ? $inner : [], $path, $value);

        return $fields;
    }
}
