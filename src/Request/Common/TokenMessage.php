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
 * A message whose only parameter is a token.
 *
 * Used to resend or verify a two-factor token, and extended by
 * {@see \Polkurier\Webservice\Http\Request\AuthorizationMessage}, which adds the login.
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
