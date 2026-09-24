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

final readonly class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Response format for methods that only acknowledge.
     *
     * These answer inconsistently — some with a bare `true`, others with an empty array — so the
     * payload cannot be cast. Reaching this point at all means the envelope said `success`, which
     * is the actual signal, so the result is true unless the API explicitly said false.
     */
    public const string FORMAT_BOOLEAN = 'boolean';

    /**
     * The wire format the payload came in, passed on to the denormalizer.
     *
     * It matters: JSON draws no distinction between an integer and a float, so a whole-numbered price
     * arrives as an int where a float is declared. The denormalizer widens one to the other only when
     * it knows the data came from JSON.
     */
    private const string FORMAT = 'json';

    /**
     * Response format for the two endpoints whose answer has no stable shape.
     *
     * The decoded payload is handed back as it arrived; there is nothing to map it onto.
     */
    public const string FORMAT_RAW_ARRAY = 'array';

    /**
     * Response format for an answer that is a map or list of plain strings.
     */
    public const string FORMAT_STRING_LIST = 'string[]';

    /**
     * Longest raw body quoted back in a MalformedResponseException, so an HTML error page from a
     * proxy does not end up pasted whole into logs.
     */
    private const int EXCERPT_LENGTH = 512;

    /**
     * @var DenormalizerInterface
     */
    private DenormalizerInterface $serializer;

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
                    $this->serializer->denormalize($responseData, ErrorMessage::class, self::FORMAT),
                );
            }

            return new ResponseWrapper(
                $httpResponse,
                new ResponseMessage($status, $this->deserializePayload($responseData, $responseFormat))
            );
        } catch (SerializerException $e) {
            // The serializer's own exceptions are outside the SDK's hierarchy, and a caller
            // catching Polkurier's base exception would miss them. A payload that will not map is
            // a malformed response by any other name.
            throw MalformedResponseException::notMappable($httpResponse, $responseFormat, $e);
        }
    }

    /**
     * @param array<string, mixed> $responseData
     * @param string $responseFormat
     *
     * @return mixed
     * @throws SerializerException when the payload will not map; create() turns it into a
     *         {@see MalformedResponseException}
     */
    private function deserializePayload(array $responseData, string $responseFormat): mixed
    {
        $payload = $responseData[ResponseMessage::FIELD_RESPONSE] ?? null;

        // Acknowledgement-only methods are not consistent about what they put in `response`:
        // cancel-style calls answer `true`, the delete_* family answers `[]`. Casting would turn
        // the empty array into false and report a successful delete as a failure, so treat the
        // success envelope itself as the answer.
        if ($responseFormat === '' || $responseFormat === self::FORMAT_BOOLEAN) {
            return $payload !== false;
        }

        // Shapes with nothing to map onto, and lists or maps of plain scalars, are already what
        // they need to be once the body is decoded.
        if ($responseFormat === self::FORMAT_RAW_ARRAY || $responseFormat === self::FORMAT_STRING_LIST) {
            return \is_array($payload) ? $payload : [];
        }

        if ($payload === []) {
            // Several endpoints answer "nothing found" with an empty array even where a single
            // object is the documented shape — get_courier_point for an unknown id,
            // get_account_verification_info for an unverified account. An empty array where an
            // object was expected means absent; where a list was expected it means no rows.
            return \str_ends_with($responseFormat, '[]') ? [] : null;
        }

        return $this->serializer->denormalize($payload, $responseFormat, self::FORMAT);
    }

    /**
     * @param string $raw
     *
     * @return string
     */
    private function excerpt(string $raw): string
    {
        $trimmed = \trim($raw);

        return \strlen($trimmed) > self::EXCERPT_LENGTH
            ? \substr($trimmed, 0, self::EXCERPT_LENGTH) . '…'
            : $trimmed;
    }
}
