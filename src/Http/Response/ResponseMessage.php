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
 * Not final, because {@see \Polkurier\Webservice\Http\Response\ErrorMessage} extends it with the
 * error fields.
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
         * On success, {@see \Polkurier\Webservice\Http\Response\ResponseFactory} builds the payload
         * without the serializer. Only errors are mapped, and for those the API sends a plain
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
     * The deserialized `response` payload: an entity, a list of entities, or a boolean for
     * methods that only acknowledge.
     *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }
}
