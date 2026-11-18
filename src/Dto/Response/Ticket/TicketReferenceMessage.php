<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A record a ticket refers to: an order, a complaint or an invoice.
 *
 * Only the number is included, and for an order its waybill number. If you need more, fetch the
 * record through its own service.
 *
 * @api
 */
final readonly class TicketReferenceMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_WAYBILL_NUMBER = 'waybill_number';
    /**-@#*/

    /**
     * @param string $number the referenced number
     * @param string|null $waybillNumber set only on order references
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        public string $number = '',

        #[Serializer\SerializedName(self::FIELD_WAYBILL_NUMBER)]
        public ?string $waybillNumber = null,
    ) {}
}
