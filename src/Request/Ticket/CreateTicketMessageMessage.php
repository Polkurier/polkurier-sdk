<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Adds a reply to an existing ticket.
 *
 * Subject to the same attachment limits as raising one. A ticket that has been closed cannot be
 * replied to.
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
     * @param array<string, string> $files attachments, base64-encoded and keyed by filename
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
         * @var array<string, string>
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
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
     * @return array<string, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
