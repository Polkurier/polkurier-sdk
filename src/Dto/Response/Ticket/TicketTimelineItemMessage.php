<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Ticket\TicketAuthorEnum;
use Polkurier\Webservice\Enum\Ticket\TicketTimelineItemTypeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One entry on a ticket's timeline.
 *
 * The timeline holds 2 kinds of entry, told apart by the type: messages someone wrote, and events on
 * the ticket. For an event, the content describes what happened.
 *
 * The read timestamp applies only to entries written by Polkurier. It records when the customer saw
 * them, and is null until then.
 *
 * @api
 */
final readonly class TicketTimelineItemMessage implements MessageInterface, ParsedResponseInterface
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
     * @param \DateTimeInterface|null $date
     * @param TicketAuthorEnum|null $origin which side it came from
     * @param string $authorName first name only
     * @param string $content the message text, or a description of the event
     * @param \DateTimeInterface|null $dateRead when the customer read it, null while unread. Ignore it on the
     *        customer's own entries
     * @param TicketFileMessage[] $files attachments on this entry
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var TicketTimelineItemTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?TicketTimelineItemTypeEnum $type = null,

        /**
         * @var \DateTimeInterface|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $date = null,

        /**
         * @var TicketAuthorEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_ORIGIN)]
        public ?TicketAuthorEnum $origin = null,

        #[Serializer\SerializedName(self::FIELD_AUTHOR_NAME)]
        #[PersonalData]
        public string $authorName = '',

        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        #[Serializer\SerializedName(self::FIELD_DATE_READ)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $dateRead = null,

        /**
         * @var TicketFileMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        public array $files = [],

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
