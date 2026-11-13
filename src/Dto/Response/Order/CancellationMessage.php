<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Result of `cancel_order`.
 */
final readonly class CancellationMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CANCELLATION = 'cancellation';
    /**-@#*/

    /**
     * @param bool $cancellation whether the order was cancelled
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_CANCELLATION)]
        private bool $cancellation = false,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancellation;
    }
}
