<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Geo;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The short-lived token the pickup-point map authenticates with.
 *
 * Deliberately not the API token: the map runs in the browser, and this is what is safe to send
 * there. Fetch one per page load rather than caching it.
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
        private string $token = '',
    ) {}

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
