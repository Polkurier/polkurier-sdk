<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Carries a bare token to the API.
 *
 * The base for anything whose only parameter is one: a two-factor token being resent or verified,
 * and {@see \Polkurier\Webservice\Http\Request\AuthorizationMessage}, which adds the
 * login to it.
 */
readonly class TokenMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOKEN = 'token';
    /**-@#*/

    /**
     * @param string $token
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TOKEN)]
        #[Validate\NotBlank]
        private string $token,
    ) {}

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
