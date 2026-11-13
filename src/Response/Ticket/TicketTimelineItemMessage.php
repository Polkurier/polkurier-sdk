<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Ticket\TicketAuthorEnum;
use Polkurier\Webservice\Enum\Ticket\TicketTimelineItemTypeEnum;
use Polkurier\Webservice\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One entry on a ticket's timeline.
 *
 * The timeline holds 2 kinds of entry, told apart by the type: messages someone wrote, and events on
 * the ticket. For an event, the content describes what happened.
 *
 * The read timestamp applies only to entries written by Polkurier. It records when the customer saw
 * them, and is null until then.
 */
readonly class TicketTimelineItemMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param TicketTimelineItemTypeEnum|null $type whether this is a message or an event
     * @param ?\DateTimeInterface $date
     * @param TicketAuthorEnum|null $origin which side it came from
     * @param string $authorName first name only
     * @param string $content the message text, or a description of the event
     * @param ?\DateTimeInterface $dateRead when the customer read it, null while unread. Ignore it on the
     *        customer's own entries
     * @param TicketFileMessage[] $files attachments on this entry
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var TicketTimelineItemTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?TicketTimelineItemTypeEnum $type = TicketTimelineItemTypeEnum::Message,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var TicketAuthorEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_ORIGIN)]
        private ?TicketAuthorEnum $origin = TicketAuthorEnum::Customer,

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

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return TicketTimelineItemTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?TicketTimelineItemTypeEnum
    {
        return $this->type;
    }

    /**
     * @return ?\DateTimeInterface null if the response leaves it out
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return TicketAuthorEnum|null null for a value this version of the SDK doesn't know
     */
    public function getOrigin(): ?TicketAuthorEnum
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
