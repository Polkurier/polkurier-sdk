<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Changes a ticket's status.
 *
 * Only {@see \Polkurier\Webservice\Enum\Ticket\TicketStatusEnum::Closed} is accepted — a customer may
 * close their own ticket and nothing else. The other states are reported but cannot be set here.
 */
readonly class SetTicketStatusMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TICKET_ID = 'ticket_id';
    public const string FIELD_STATUS = 'status';
    /**-@#*/

    /**
     * @param string $ticketId the ticket UUID
     * @param TicketStatusEnum $status only CLOSED is accepted
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TICKET_ID)]
        #[Validate\NotBlank]
        private string $ticketId,

        /**
         * @var TicketStatusEnum
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private TicketStatusEnum $status = TicketStatusEnum::Closed,
    ) {}

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    /**
     * @return TicketStatusEnum
     */
    public function getStatus(): TicketStatusEnum
    {
        return $this->status;
    }
}
