<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\TwoFactorToken;

use Polkurier\Webservice\Request\Common\TokenMessage;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Submits the code the user entered against a registered token.
 */
readonly class VerifyMessage extends TokenMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CODE = 'code';
    /**-@#*/

    /**
     * @param string $token
     * @param string $code
     */
    public function __construct(
        string $token,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CODE)]
        #[Validate\NotBlank]
        private string $code,
    ) {
        parent::__construct($token);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
