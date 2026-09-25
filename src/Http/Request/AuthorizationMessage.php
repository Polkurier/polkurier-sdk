<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Request;

use Polkurier\Webservice\Request\Common\TokenMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An account id and API token.
 *
 * The login is the numeric customer id, not an email address. Generate the token in the customer
 * panel under Settings > API token.
 *
 * It is serialized into every authenticated request, so logging a request body also logs the
 * token.
 */
readonly class AuthorizationMessage extends TokenMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_LOGIN = 'login';
    /**-@#*/

    /**
     * @param string $token
     * @param string $login
     */
    public function __construct(
        string $token,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LOGIN)]
        private string $login,
    ) {
        parent::__construct($token);
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }
}
