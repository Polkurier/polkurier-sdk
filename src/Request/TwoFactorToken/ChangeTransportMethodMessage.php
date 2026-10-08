<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\TwoFactorToken;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Switches a pending two-factor confirmation to a different delivery channel.
 *
 * For a customer who cannot receive the code the way it was first sent, e.g. no signal for
 * a text message. The available channels are listed on the token returned by registering.
 *
 * Switching sends the code again over the new channel, so the same rate limit as a resend applies.
 */
readonly class ChangeTransportMethodMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOKEN = 'token';
    public const string FIELD_METHOD = 'method';
    /**-@#*/

    /**
     * @param string $token the token id from registering
     * @param string $method id of the channel to switch to, from the token's available methods
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TOKEN)]
        #[Validate\NotBlank]
        private string $token,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_METHOD)]
        #[Validate\NotBlank]
        private string $method,
    ) {}

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
