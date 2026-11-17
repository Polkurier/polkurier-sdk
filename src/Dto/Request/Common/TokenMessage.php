<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A message carrying a token.
 *
 * {@see \Polkurier\Webservice\Dto\Request\TwoFactorToken\ResendMessage} and
 * {@see \Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage} carry a two-factor token,
 * {@see \Polkurier\Webservice\Dto\Request\AuthorizationMessage} the API token.
 *
 * @api
 */
abstract readonly class TokenMessage implements MessageInterface
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
        #[Sensitive]
        public string $token,
    ) {}
}
