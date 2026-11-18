<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Geo;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The short-lived token the pickup-point map authenticates with.
 *
 * This is not the API token. The map runs in the browser, and this token is safe to send there. It
 * expires 4 hours after it's issued.
 *
 * @api
 */
final readonly class TokenMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOKEN = 'token';
    /**-@#*/

    /**
     * @param string $token
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_TOKEN)]
        #[Sensitive]
        public string $token = '',

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
