<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a reply to an existing ticket.
 *
 * The same attachment limits apply as when creating a ticket. You cannot reply to a closed ticket.
 */
#[Validate\RequiredWhen(field: 'content', property: 'files', equals: [], because: 'is required without files')]
final readonly class CreateTicketMessageMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TICKET_ID = 'ticket_id';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * @param string $ticketId the ticket UUID
     * @param string $content the message body, may be empty when files are attached
     * @param TicketAttachmentMessage[] $files attachments, up to 5
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TICKET_ID)]
        #[Validate\NotBlank]
        public string $ticketId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content,

        /**
         * @var TicketAttachmentMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[OmitWhenEmpty]
        #[Validate\Valid]
        #[Validate\Each(Validate\OfType::class, TicketAttachmentMessage::class)]
        public array $files = [],
    ) {}
}
