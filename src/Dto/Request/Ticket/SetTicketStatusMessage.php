<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Changes a ticket's status.
 *
 * Only {@see TicketStatusEnum::Closed} is accepted.
 *
 * @api
 */
final readonly class SetTicketStatusMessage implements MessageInterface
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
        public string $ticketId,

        /**
         * @var TicketStatusEnum
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public TicketStatusEnum $status = TicketStatusEnum::Closed,
    ) {}
}
