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
 * Names one attachment to download.
 *
 * Attachments are addressed by the message they belong to plus their filename, so a filename is only
 * unique within its message.
 */
readonly class GetTicketMessageFileMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MESSAGE_ID = 'message_id';
    public const string FIELD_FILENAME = 'filename';
    /**-@#*/

    /**
     * @param string $messageId UUID of the timeline entry holding the file
     * @param string $filename the filename as the timeline reports it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE_ID)]
        #[Validate\NotBlank]
        private string $messageId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        #[Validate\NotBlank]
        private string $filename,
    ) {}

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }
}
