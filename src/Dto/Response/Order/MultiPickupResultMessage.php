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
 * Per-order outcome of `create_multi_order_pickup`.
 *
 * Orders succeed or fail one by one, and the call succeeds even when none goes through, so check
 * both lists.
 *
 * @api
 */
final readonly class MultiPickupResultMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_SUCCESS = 'success';
    public const string FIELD_FAILED = 'failed';
    /**-@#*/

    /**
     * @param string[] $success order numbers a pickup was booked for
     * @param array<string, string> $failed reason for each rejection, keyed by the order number as
     *        sent in `orders`
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_SUCCESS)]
        public array $success = [],

        /**
         * @var array<string, string>
         */
        #[Serializer\SerializedName(self::FIELD_FAILED)]
        public array $failed = [],

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
