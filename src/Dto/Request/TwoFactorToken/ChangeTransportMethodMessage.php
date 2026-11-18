<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\TwoFactorToken;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Switches a pending two-factor confirmation to a different delivery channel.
 *
 * For a customer who cannot receive the code the way it was first sent. The available channels are
 * listed on the token returned by registering.
 *
 * Switching sends the code over the new channel at once; the resend window doesn't apply.
 *
 * @api
 */
final readonly class ChangeTransportMethodMessage implements MessageInterface
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
        #[Serializer\SerializedName(self::FIELD_TOKEN)]
        #[Validate\NotBlank]
        #[Sensitive]
        public string $token,

        #[Serializer\SerializedName(self::FIELD_METHOD)]
        #[Validate\NotBlank]
        public string $method,
    ) {}
}
