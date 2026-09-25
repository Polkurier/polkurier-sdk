<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Request;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The JSON envelope wrapping every request.
 *
 * `authorization` and `data` are left out when empty, so unauthenticated methods and methods
 * without parameters use the same envelope.
 */
final class RequestMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_API_METHOD = 'apimethod';
    public const string FIELD_AUTHORIZATION = 'authorization';
    public const string FIELD_DATA = 'data';
    /**-@#*/

    /**
     * @param ApiMethodInterface $apiMethod
     * @param AuthorizationMessage|null $authorization
     * @param MessageInterface|null $data
     */
    public function __construct(
        /**
         * @var ApiMethodInterface
         */
        #[Serializer\SerializedName(self::FIELD_API_METHOD)]
        private readonly ApiMethodInterface $apiMethod,

        /**
         * @var AuthorizationMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_AUTHORIZATION)]
        #[OmitWhenEmpty]
        private readonly ?AuthorizationMessage $authorization = null,

        /**
         * @var MessageInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATA)]
        #[OmitWhenEmpty]
        private readonly ?MessageInterface $data = null,
    ) {}

    /**
     * @return ApiMethodInterface
     */
    public function getApiMethod(): ApiMethodInterface
    {
        return $this->apiMethod;
    }

    /**
     * @return AuthorizationMessage|null
     */
    public function getAuthorization(): ?AuthorizationMessage
    {
        return $this->authorization;
    }

    /**
     * @return MessageInterface|null
     */
    public function getData(): ?MessageInterface
    {
        return $this->data;
    }
}
