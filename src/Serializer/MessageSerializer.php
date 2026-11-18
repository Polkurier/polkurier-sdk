<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Api\SerializerInterface;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Writes every request body the SDK sends and reads every response body it receives.
 *
 * {@see \Polkurier\Webservice\Http\Request\RequestFactory} builds every body through
 * {@see self::encode()} and {@see \Polkurier\Webservice\Http\Response\ResponseFactory} reads
 * every response through {@see self::readResponse()}, so a caller handed this object through
 * {@see \Polkurier\Webservice\PolkurierWebservice::serializer()} gets the same rules the client
 * itself follows: the quirks of each method, the wire formats, and the record of how a response
 * differed from what the SDK expects.
 *
 * It holds nothing of what it read, so one instance serves every caller. The serializer behind it
 * is the SDK's own, see {@see \Polkurier\Webservice\Api\SerializerInterface} and
 * {@see SerializerProvider}.
 */
readonly class MessageSerializer implements SerializerInterface
{
    /**
     * The wire format of the payload, passed to the denormalizer.
     *
     * JSON doesn't distinguish integers from floats, so a whole-number price arrives as an int
     * where a float is declared. The denormalizer only converts it when it knows the data is JSON.
     */
    protected const string WIRE_FORMAT = 'json';

    /**
     * Maximum length of the body quoted in an exception, so a proxy's HTML error page doesn't end
     * up in the logs in full.
     */
    protected const int EXCERPT_LENGTH = 512;

    /**
     * The constructor parameter of every {@see ParsedResponseInterface} class that takes the body.
     */
    protected const string RAW_RESPONSE_PARAMETER = 'rawResponse';

    /**
     * @param SerializerProvider $serializerProvider
     */
    public function __construct(
        /**
         * @var SerializerProvider
         */
        protected SerializerProvider $serializerProvider,
    ) {}

    /**
     * @param MessageInterface $message
     *
     * @return string
     * @throws InvalidArgumentException when the message cannot be written
     */
    public function encode(MessageInterface $message): string
    {
        try {
            return $this->serializerProvider->getSerializer()
                ->serialize($message, static::WIRE_FORMAT);
        } catch (SerializerException $e) {
            throw new InvalidArgumentException(
                \sprintf('%s could not be written: %s', $message::class, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @template T of MessageInterface
     *
     * @param string $payload
     * @param class-string<T> $type
     *
     * @return T
     * @throws InvalidArgumentException when the payload cannot be read as that class
     * @throws \ArgumentCountError when the class cannot be built from any payload, which is a
     *         defect in the class rather than in the payload
     */
    public function decode(string $payload, string $type): MessageInterface
    {
        try {
            $message = $this->serializerProvider->getSerializer()
                ->deserialize($payload, $type, static::WIRE_FORMAT);
        } catch (\ArgumentCountError $e) {
            // The class cannot be built from a payload at all, which is a defect in the class and
            // not in the payload, so it is not reported as one.
            throw $e;
        } catch (SerializerException | \TypeError $e) {
            throw new InvalidArgumentException(
                \sprintf('The payload could not be read as %s: %s', $type, $e->getMessage()),
                0,
                $e,
            );
        }

        // A payload holding none of the class's fields reads as null, the way an inner object
        // that arrived empty does. At the top level there is no property to take that null, so
        // it is the refusal this method documents rather than a return value.
        if (!$message instanceof $type) {
            throw new InvalidArgumentException(
                \sprintf('The payload could not be read as %s: none of its fields arrived.', $type),
            );
        }

        return $message;
    }

    /**
     * @param string $body
     * @param string $responseFormat
     *
     * @return ReadResponse
     * @throws InvalidArgumentException when the body is not an API response, or its payload cannot
     *         be read as that class
     * @throws \ArgumentCountError when the class the payload maps onto cannot be built from any
     *         payload, which is a defect in that class rather than in what arrived
     */
    public function readResponse(string $body, string $responseFormat): ReadResponse
    {
        $responseData = \json_decode($body, true);

        if (!\is_array($responseData)) {
            throw new InvalidArgumentException(
                \sprintf('Expected a JSON object from the Polkurier API, got body: "%s"', static::makeExcerpt($body)),
            );
        }

        $rawStatus = $responseData[ResponseMessage::FIELD_STATUS] ?? null;
        if (!\is_string($rawStatus)) {
            throw new InvalidArgumentException(
                \sprintf('The response has no "status" field, body: "%s"', static::makeExcerpt($body)),
            );
        }

        // Some methods send the status in capitals, e.g. `ERROR` for a rejected contact form.
        $status = ResponseStatusEnum::tryFrom(\strtolower($rawStatus));
        if ($status === null) {
            throw new InvalidArgumentException(\sprintf('The response status "%s" is unknown.', $rawStatus));
        }
        $responseData[ResponseMessage::FIELD_STATUS] = $status->value;

        $drift = new ResponseDriftCollector();

        try {
            if ($status === ResponseStatusEnum::Error) {
                $error = $this->serializerProvider->getSerializer()->denormalize(
                    $responseData,
                    ErrorMessage::class,
                    static::WIRE_FORMAT,
                    [ResponseDriftCollector::CONTEXT_KEY => $drift],
                );

                return new ReadResponse($error, $drift->getDrift());
            }

            $payload = $this->readPayload($responseData, $responseFormat, $drift, $body);
        } catch (\ArgumentCountError $e) {
            // The class the method asks for cannot be built from a payload at all, which is a
            // defect in that class rather than in what the API sent.
            throw $e;
        } catch (SerializerException | \TypeError $e) {
            // Serializer exceptions are outside the SDK's hierarchy, so a caller catching the base
            // exception would miss them, and a constructor refusing what the denormalizer built
            // raises a TypeError that is outside it too.
            throw new InvalidArgumentException(
                \sprintf('The payload could not be read as %s: %s', $responseFormat, $e->getMessage()),
                0,
                $e,
            );
        }

        return new ReadResponse(new ResponseMessage($status, $payload), $drift->getDrift());
    }

    /**
     * The payload of a successful response, in the shape the method asks for.
     *
     * @param array<string, mixed> $responseData
     * @param string $responseFormat
     * @param ResponseDriftCollector $drift receives how the payload differs from what the SDK expects
     * @param string|null $body the body, which the payload keeps when it is a
     *        {@see ParsedResponseInterface}
     *
     * @return mixed
     * @throws SerializerException when the payload doesn't map
     * @throws InvalidArgumentException when a class on the way declares a field in more than one wire format
     * @throws \TypeError when a constructor refuses what the denormalizer built
     * @throws \ArgumentCountError when the class the payload maps onto cannot be built from any
     *         payload
     */
    protected function readPayload(
        array $responseData,
        string $responseFormat,
        ResponseDriftCollector $drift,
        ?string $body,
    ): mixed {
        $payload = $responseData[ResponseMessage::FIELD_RESPONSE] ?? null;

        // Acknowledge-only methods differ in what they put in `response`. Cancel-style calls
        // return `true`, the delete_* methods return `[]`. Casting would turn `[]` into false and
        // report a successful delete as a failure, so the success status is the answer.
        if ($responseFormat === '' || $responseFormat === static::FORMAT_BOOLEAN) {
            return $payload !== false;
        }

        // Free-form payloads and lists or maps of plain scalars need no mapping after decoding. The
        // service helpers check the shape, so anything else is passed on as it came.
        if ($responseFormat === static::FORMAT_RAW_ARRAY || $responseFormat === static::FORMAT_STRING_LIST) {
            return $payload;
        }

        if ($payload === [] || $payload === null) {
            // Several endpoints return an empty array for "nothing found" even where a single
            // object is documented, e.g. get_courier_point for an unknown id, or
            // get_account_verification_info for an unverified account. An empty or missing payload
            // means null where an object was expected, no rows where a list was expected, and an
            // empty page where a page was expected, e.g. get_money_box_payouts for an unknown
            // payout number.
            return match (true) {
                \str_ends_with($responseFormat, '[]') => [],
                \is_subclass_of($responseFormat, PagedListMessage::class)
                    => new $responseFormat(...[static::RAW_RESPONSE_PARAMETER => $body]),
                default => null,
            };
        }

        if (!\is_array($payload)) {
            // The object normalizer would cast a scalar to an array and build an object from its
            // defaults, so refuse it here.
            throw new UnexpectedValueException(
                \sprintf('Expected an object or a list for %s, got %s.', $responseFormat, \get_debug_type($payload)),
            );
        }

        // A payload that differs from what the SDK expects is read anyway, so one change on the
        // API's side doesn't make the whole response unreadable. Each one is recorded.
        $context = [ResponseDriftCollector::CONTEXT_KEY => $drift];
        if (\is_subclass_of($responseFormat, ParsedResponseInterface::class)) {
            // Only the object the method returns keeps the body, not the objects inside it.
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$responseFormat] = [
                static::RAW_RESPONSE_PARAMETER => $body,
            ];
        }

        return $this->serializerProvider->getSerializer()
            ->denormalize($payload, $responseFormat, static::WIRE_FORMAT, $context);
    }

    /**
     * @param string $raw
     *
     * @return string
     */
    protected static function makeExcerpt(string $raw): string
    {
        $trimmed = \trim($raw);

        // Cut on a character, not a byte: half of a multi-byte character makes the message
        // unencodable, and a log formatter then drops the record carrying it.
        return \strlen($trimmed) > static::EXCERPT_LENGTH
            ? \mb_strcut($trimmed, 0, static::EXCERPT_LENGTH) . '…'
            : $trimmed;
    }
}
