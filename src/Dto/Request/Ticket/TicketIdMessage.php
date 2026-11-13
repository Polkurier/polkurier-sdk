<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies one ticket by its UUID.
 *
 * @api
 */
final readonly class TicketIdMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TICKET_ID = 'ticket_id';
    /**-@#*/

    /**
     * @param string $ticketId the ticket UUID
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TICKET_ID)]
        #[Validate\NotBlank]
        public string $ticketId,
    ) {}
}
