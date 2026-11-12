<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Util;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Confirmation that a set of credentials is valid.
 */
readonly class AuthConfirmationMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }
}
