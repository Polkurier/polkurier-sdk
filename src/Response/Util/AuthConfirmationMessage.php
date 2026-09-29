<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Util;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Confirmation that a set of credentials is valid.
 */
readonly class AuthConfirmationMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AUTHORIZATION = 'authorization';
    /**-@#*/

    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_AUTHORIZATION)]
        private bool $authorized,
    ) {}

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }
}
