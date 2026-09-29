<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketOriginEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Creates a support ticket.
 *
 * Only topics returned by
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getContactTopics()} are accepted.
 *
 * Attachments are sent inline as base64. `get_system_info` reports the limits under `tickets`:
 * maximum file size, maximum image size and how many files can be attached at once. Check them
 * before sending.
 */
readonly class CreateTicketMessage implements MessageInterface
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
     * @param string $content the message body
     * @param string $name the sender's name, where it differs from the account holder
     * @param ?TicketOriginEnum $origin which channel created it, defaults to API
     * @param array<string, string> $files attachments, base64-encoded and keyed by filename
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TOPIC_ID)]
        #[Validate\NotBlank]
        private string $topicId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        #[Validate\NotBlank]
        private string $content,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var ?TicketOriginEnum
         */
        #[Serializer\SerializedName(self::FIELD_ORIGIN)]
        #[OmitWhenEmpty]
        private ?TicketOriginEnum $origin = null,

        /**
         * @var array<string, string>
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\OfType::class, 'string')]
        private array $files = [],
    ) {}

    /**
     * @return string
     */
    public function getTopicId(): string
    {
        return $this->topicId;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ?TicketOriginEnum
     */
    public function getOrigin(): ?TicketOriginEnum
    {
        return $this->origin;
    }

    /**
     * @return array<string, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
