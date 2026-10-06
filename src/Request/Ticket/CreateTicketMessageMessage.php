<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a reply to an existing ticket.
 *
 * The same attachment limits apply as when creating a ticket. You cannot reply to a closed ticket.
 */
readonly class CreateTicketMessageMessage implements MessageInterface
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
     * @param string $content the message body
     * @param TicketAttachmentMessage[] $files attachments, up to 5
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TICKET_ID)]
        #[Validate\NotBlank]
        private string $ticketId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        #[Validate\NotBlank]
        private string $content,

        /**
         * @var TicketAttachmentMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[OmitWhenEmpty]
        #[Validate\Valid]
        #[Validate\Each(Validate\OfType::class, TicketAttachmentMessage::class)]
        private array $files = [],
    ) {}

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return TicketAttachmentMessage[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
