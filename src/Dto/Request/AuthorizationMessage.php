<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request;

use Polkurier\Webservice\Dto\Request\Common\TokenMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An account id and API token.
 *
 * The login is the numeric customer id, not an email address. Generate the token in the customer
 * panel under Settings > API token.
 *
 * It is serialized into the body of every authenticated request. The SDK's own logs hide the token
 * unless `logSensitiveData` is on.
 *
 * @api
 */
final readonly class AuthorizationMessage extends TokenMessage
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

        #[Serializer\SerializedName(self::FIELD_LOGIN)]
        public string $login,
    ) {
        parent::__construct($token);
    }
}
