<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketAuthorEnum;
use Polkurier\Webservice\Enum\Ticket\TicketTimelineItemTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One entry on a ticket's timeline.
 *
 * The timeline mixes two kinds of entry, distinguished by the type: messages someone wrote, and
 * actions that happened to the ticket. For an action the content is a description of the event
 * rather than prose from a person.
 *
 * The read timestamp is only meaningful on entries written by Polkurier: it records when the
 * customer saw them, and is null until they did.
 */
readonly class TicketTimelineItemMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DATE = 'date';
    public const string FIELD_ORIGIN = 'origin';
    public const string FIELD_AUTHOR_NAME = 'author_name';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_DATE_READ = 'date_read';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * @param string $id
     * @param TicketTimelineItemTypeEnum $type whether this is a message or an event
     * @param ?\DateTimeInterface $date
     * @param TicketAuthorEnum $origin which side it came from
     * @param string $authorName first name only
     * @param string $content the message text, or a description of the event
     * @param ?\DateTimeInterface $dateRead when the customer read it; null while unread, and meaningless on their own entries
     * @param TicketFileMessage[] $files attachments on this entry
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var TicketTimelineItemTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private TicketTimelineItemTypeEnum $type = TicketTimelineItemTypeEnum::Message,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var TicketAuthorEnum
         */
        #[Serializer\SerializedName(self::FIELD_ORIGIN)]
        private TicketAuthorEnum $origin = TicketAuthorEnum::Customer,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_AUTHOR_NAME)]
        private string $authorName = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        private string $content = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_READ)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateRead = null,

        /**
         * @var TicketFileMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        private array $files = [],
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return TicketTimelineItemTypeEnum
     */
    public function getType(): TicketTimelineItemTypeEnum
    {
        return $this->type;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return TicketAuthorEnum
     */
    public function getOrigin(): TicketAuthorEnum
    {
        return $this->origin;
    }

    /**
     * @return string
     */
    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDateRead(): ?\DateTimeInterface
    {
        return $this->dateRead;
    }

    /**
     * @return TicketFileMessage[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
