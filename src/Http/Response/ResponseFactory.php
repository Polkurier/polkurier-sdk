<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Polkurier\Webservice\Serializer\UnknownEnumValues;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns an HTTP response from the API into a
 * {@see \Polkurier\Webservice\Http\Response\ResponseWrapper}.
 *
 * Decodes the body, reads the envelope's status, and maps the payload into the type the service
 * asked for, or into an {@see \Polkurier\Webservice\Http\Response\ErrorMessage} for an error. A
 * body that isn't an API envelope, or a payload that doesn't map, throws a
 * {@see \Polkurier\Webservice\Exception\MalformedResponseException}.
 *
 * Enum values the SDK doesn't know read as null, or are left out of a list, and are recorded on the
 * wrapper (see {@see \Polkurier\Webservice\Http\Response\ResponseWrapper::getUnknownEnumValues()}).
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
     * Response format for the 2 endpoints whose answer has no fixed shape.
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
        $rawResponse = $httpResponse->getBody()->getContents();
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
            if ($status === ResponseStatusEnum::Error) {
                return new ResponseWrapper(
                    $httpResponse,
                    $this->serializer->denormalize($responseData, ErrorMessage::class, static::FORMAT),
                );
            }

            $unknownEnumValues = new UnknownEnumValues();
            $payload = $this->deserializePayload($responseData, $responseFormat, $unknownEnumValues);

            return new ResponseWrapper(
                $httpResponse,
                new ResponseMessage($status, $payload),
                $unknownEnumValues->all(),
            );
        } catch (SerializerException $e) {
            // Serializer exceptions are outside the SDK's hierarchy, so a caller catching the base
            // exception would miss them. A payload that doesn't map is a malformed response.
            throw MalformedResponseException::notMappable($httpResponse, $responseFormat, $e);
        }
    }

    /**
     * @param array<string, mixed> $responseData
     * @param string $responseFormat
     * @param UnknownEnumValues $unknownEnumValues receives the enum values the payload had that the
     *        SDK doesn't know
     *
     * @return mixed
     * @throws SerializerException when the payload doesn't map. create() turns it into a
     *         {@see MalformedResponseException}
     */
    protected function deserializePayload(
        array $responseData,
        string $responseFormat,
        UnknownEnumValues $unknownEnumValues = new UnknownEnumValues(),
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
            // means null where an object was expected, and no rows where a list was expected.
            return \str_ends_with($responseFormat, '[]') ? [] : null;
        }

        if (!\is_array($payload)) {
            // The object normalizer would cast a scalar to an array and build an object from its
            // defaults, so refuse it here.
            throw new UnexpectedValueException(
                \sprintf('Expected an object or a list for %s, got %s.', $responseFormat, \get_debug_type($payload)),
            );
        }

        // An enum value this version of the SDK doesn't know reads as null, and is left out of a
        // list, so one new value doesn't make the whole response unreadable. Each one is recorded.
        return $this->serializer->denormalize($payload, $responseFormat, static::FORMAT, [
            UnknownEnumValues::CONTEXT_KEY => $unknownEnumValues,
        ]);
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
