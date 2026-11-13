<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketOriginEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Creates a support ticket.
 *
 * Takes a topic id from {@see
 * \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getContactTopics()}; an unknown one
 * files the ticket under a general subject.
 *
 * Attachments are sent inline as base64, see
 * {@see \Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage} for what the API accepts.
 * `get_system_info` reports the limits under `tickets`: maximum file size, maximum image size and
 * how many files can be attached at once.
 *
 * @api
 */
#[Validate\RequiredWhen(field: 'content', property: 'files', equals: [], reason: 'is required without files')]
final readonly class CreateTicketMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TOPIC_ID = 'topic_id';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_NAME = 'name';
    public const string FIELD_ORIGIN = 'origin';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * @param string $topicId a topic id from getContactTopics()
     * @param string $content the message body, may be empty when files are attached
     * @param string $name the sender's name
     * @param ?TicketOriginEnum $origin which channel created it, defaults to API
     * @param TicketAttachmentMessage[] $files attachments, up to 5
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TOPIC_ID)]
        #[Validate\NotBlank]
        public string $topicId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var ?TicketOriginEnum
         */
        #[Serializer\SerializedName(self::FIELD_ORIGIN)]
        #[OmitWhenEmpty]
        public ?TicketOriginEnum $origin = null,

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
