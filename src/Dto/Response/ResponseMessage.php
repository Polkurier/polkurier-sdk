<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The envelope of a successful API response: its status and its payload.
 *
 * @api
 */
final readonly class ResponseMessage implements MessageInterface
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
        public ResponseStatusEnum $status,

        /**
         * The payload, in the shape the service method asks for, e.g. an object, a list, or a
         * boolean for methods that only acknowledge.
         *
         * @var mixed
         */
        #[Serializer\SerializedName(self::FIELD_RESPONSE)]
        public mixed $response,
    ) {}
}
