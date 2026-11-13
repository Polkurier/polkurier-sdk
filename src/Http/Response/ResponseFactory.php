<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Turns an HTTP response from the API into a
 * {@see \Polkurier\Webservice\Http\Response\ResponseWrapper}.
 *
 * Decodes the body, reads the envelope's status, and maps the payload into the type the service
 * asked for, or into an {@see \Polkurier\Webservice\Dto\Response\ErrorMessage} for an error. A
 * body that isn't an API envelope, or a payload that doesn't map, throws a
 * {@see \Polkurier\Webservice\Exception\MalformedResponseException}.
 *
 * A payload that differs from what the SDK expects is read anyway, as
 * {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum} describes, and each difference is
 * recorded on the wrapper (see {@see \Polkurier\Webservice\Http\Response\ResponseWrapper::getDrift()}).
 */
readonly class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Response format for methods that only acknowledge.
     *
     * Some of them answer with `true`, others with an empty array, so the payload can't be cast.
     * Getting this far means the envelope said `success`, so the result is true unless the API
     * explicitly returned false.
     */
    public const string FORMAT_BOOLEAN = 'boolean';

    /**
     * The wire format of the payload, passed to the denormalizer.
     *
     * JSON doesn't distinguish integers from floats, so a whole-number price arrives as an int
     * where a float is declared. The denormalizer only converts it when it knows the data is JSON.
     */
    protected const string FORMAT = 'json';

    /**
     * Response format for an answer with no fixed shape.
     *
     * The decoded payload is returned as is.
     */
    public const string FORMAT_RAW_ARRAY = 'array';

    /**
     * Response format for an answer that is a map or list of plain strings.
     */
    public const string FORMAT_STRING_LIST = 'string[]';

    /**
     * Maximum length of the raw body quoted in a
     * {@see \Polkurier\Webservice\Exception\MalformedResponseException}, so a proxy's HTML error
     * page doesn't end up in the logs in full.
     */
    protected const int EXCERPT_LENGTH = 512;

    /**
     * The constructor parameter of every {@see ParsedResponseInterface} class that takes the body.
     */
    protected const string RAW_RESPONSE_PARAMETER = 'rawResponse';

    /**
     * @var DenormalizerInterface
     */
    protected DenormalizerInterface $serializer;

    /**
     * @param SerializerProviderInterface $serializerProvider
     */
    public function __construct(SerializerProviderInterface $serializerProvider)
    {
        $this->serializer = $serializerProvider->getSerializer();
    }

    /**
     * @param ResponseInterface $httpResponse
     * @param string $responseFormat
     *
     * @return ResponseWrapper
     * @throws MalformedResponseException
     */
    public function create(ResponseInterface $httpResponse, string $responseFormat): ResponseWrapper
    {
        // Read from the start, so the raw body is complete even if the client left the stream
        // elsewhere.
        $body = $httpResponse->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $rawResponse = $body->getContents();
        $responseData = \json_decode($rawResponse, true);

        if (!\is_array($responseData)) {
            throw MalformedResponseException::notJson($httpResponse, $this->excerpt($rawResponse));
        }

        $rawStatus = $responseData[ResponseMessage::FIELD_STATUS] ?? null;
        if (!\is_string($rawStatus)) {
            throw MalformedResponseException::missingStatus($httpResponse, $this->excerpt($rawResponse));
        }

        // Some methods send the status in capitals, e.g. `ERROR` for a rejected contact form.
        $status = ResponseStatusEnum::tryFrom(\strtolower($rawStatus));
        if ($status === null) {
            throw MalformedResponseException::unknownStatus($httpResponse, $rawStatus);
        }
        $responseData[ResponseMessage::FIELD_STATUS] = $status->value;

        try {
            $drift = new ResponseDriftCollector();
            if ($status === ResponseStatusEnum::Error) {
                return new ResponseWrapper(
                    $httpResponse,
                    $this->serializer->denormalize($responseData, ErrorMessage::class, static::FORMAT, [
                        ResponseDriftCollector::CONTEXT_KEY => $drift,
                    ]),
                    $drift->all(),
                );
            }

            $payload = $this->deserializePayload($responseData, $responseFormat, $drift, $rawResponse);

            return new ResponseWrapper($httpResponse, new ResponseMessage($status, $payload), $drift->all());
        } catch (SerializerException $e) {
            // Serializer exceptions are outside the SDK's hierarchy, so a caller catching the base
            // exception would miss them. A payload that doesn't map is a malformed response.
            throw MalformedResponseException::notMappable($httpResponse, $responseFormat, $e);
        }
    }

    /**
     * @param array<string, mixed> $responseData
     * @param string $responseFormat
     * @param ResponseDriftCollector $drift receives how the payload differs from what the SDK expects
     * @param string|null $rawResponse the body, which the payload keeps when it is a
     *        {@see ParsedResponseInterface}
     *
     * @return mixed
     * @throws SerializerException when the payload doesn't map. create() turns it into a
     *         {@see MalformedResponseException}
     */
    protected function deserializePayload(
        array $responseData,
        string $responseFormat,
        ResponseDriftCollector $drift = new ResponseDriftCollector(),
        ?string $rawResponse = null,
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
                    => new $responseFormat(...[static::RAW_RESPONSE_PARAMETER => $rawResponse]),
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

        // A payload that differs from what this version of the SDK expects is read anyway, so one
        // change on the API's side doesn't make the whole response unreadable. Each one is recorded.
        $context = [ResponseDriftCollector::CONTEXT_KEY => $drift];
        if (\is_subclass_of($responseFormat, ParsedResponseInterface::class)) {
            // Only the object the method returns keeps the body, not the objects inside it.
            $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$responseFormat] = [
                static::RAW_RESPONSE_PARAMETER => $rawResponse,
            ];
        }

        return $this->serializer->denormalize($payload, $responseFormat, static::FORMAT, $context);
    }

    /**
     * @param string $raw
     *
     * @return string
     */
    protected function excerpt(string $raw): string
    {
        $trimmed = \trim($raw);

        return \strlen($trimmed) > static::EXCERPT_LENGTH
            ? \substr($trimmed, 0, static::EXCERPT_LENGTH) . '…'
            : $trimmed;
    }
}
