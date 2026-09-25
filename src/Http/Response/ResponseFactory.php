<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Serializer\SerializerProviderInterface;
use Psr\Http\Message\ResponseInterface;

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
     * Maximum length of the raw body quoted in a MalformedResponseException, so a proxy's HTML
     * error page doesn't end up in the logs in full.
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

        $status = ResponseStatusEnum::tryFrom($rawStatus);
        if ($status === null) {
            throw MalformedResponseException::unknownStatus($httpResponse, $rawStatus);
        }

        try {
            if ($status === ResponseStatusEnum::Error) {
                return new ResponseWrapper(
                    $httpResponse,
                    $this->serializer->denormalize($responseData, ErrorMessage::class, static::FORMAT),
                );
            }

            return new ResponseWrapper(
                $httpResponse,
                new ResponseMessage($status, $this->deserializePayload($responseData, $responseFormat))
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
     *
     * @return mixed
     * @throws SerializerException when the payload doesn't map. create() turns it into a
     *         {@see MalformedResponseException}
     */
    protected function deserializePayload(array $responseData, string $responseFormat): mixed
    {
        $payload = $responseData[ResponseMessage::FIELD_RESPONSE] ?? null;

        // Acknowledge-only methods differ in what they put in `response`. Cancel-style calls
        // return `true`, the delete_* methods return `[]`. Casting would turn `[]` into false and
        // report a successful delete as a failure, so the success status is the answer.
        if ($responseFormat === '' || $responseFormat === static::FORMAT_BOOLEAN) {
            return $payload !== false;
        }

        // Free-form payloads and lists or maps of plain scalars need no mapping after decoding.
        if ($responseFormat === static::FORMAT_RAW_ARRAY || $responseFormat === static::FORMAT_STRING_LIST) {
            return \is_array($payload) ? $payload : [];
        }

        if ($payload === []) {
            // Several endpoints return an empty array for "nothing found" even where a single
            // object is documented, e.g. get_courier_point for an unknown id, or
            // get_account_verification_info for an unverified account. An empty array means null
            // where an object was expected, and no rows where a list was expected.
            return \str_ends_with($responseFormat, '[]') ? [] : null;
        }

        return $this->serializer->denormalize($payload, $responseFormat, static::FORMAT);
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
