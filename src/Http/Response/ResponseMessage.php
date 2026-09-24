<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A successful API response: the envelope status and the decoded payload.
 *
 * The one part of the envelope that is not final, because {@see ErrorMessage} is the same envelope
 * with the error fields on it. Nothing else should extend it.
 */
readonly class ResponseMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_RESPONSE = 'response';
    /**-@#*/

    /**
     * @param ResponseStatusEnum $status
     * @param mixed $response
     */
    public function __construct(
        /**
         * @var ResponseStatusEnum
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private ResponseStatusEnum $status,

        /**
         * On a success the payload is built by ResponseFactory and never passes through the
         * serializer at all; only the error case is mapped, and there the API sends a plain
         * message string.
         *
         * @var mixed
         */
        #[Serializer\SerializedName(self::FIELD_RESPONSE)]
        private mixed $response,
    ) {}

    /**
     * @return ResponseStatusEnum
     */
    public function getStatus(): ResponseStatusEnum
    {
        return $this->status;
    }

    /**
      * The deserialized `response` payload: an entity, a list of entities, or a bare
      * boolean for methods that only acknowledge.
      *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }
}
