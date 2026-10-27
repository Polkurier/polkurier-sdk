<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A record a ticket refers to: an order, a complaint or an invoice.
 *
 * Only the number is included, and for an order its waybill number. If you need more, fetch the
 * record through its own service.
 */
readonly class TicketReferenceMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_WAYBILL_NUMBER = 'waybill_number';
    /**-@#*/

    /**
     * @param string $number the referenced number
     * @param ?string $waybillNumber set only on order references
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number = '',

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_WAYBILL_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $waybillNumber = null,
    ) {}

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return ?string
     */
    public function getWaybillNumber(): ?string
    {
        return $this->waybillNumber;
    }
}
