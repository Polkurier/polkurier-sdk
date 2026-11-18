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
 * Whether the shipment asked about can be placed for a collective pickup. False also when the API
 * couldn't evaluate the request.
 *
 * @api
 */
final readonly class MultiPickupAvailabilityMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_AVAILABLE = 'isMultiPickupAvailable';
    /**-@#*/

    /**
     * @param bool $available
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_AVAILABLE)]
        public bool $available = false,

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
