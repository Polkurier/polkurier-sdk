<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Contact\ContactFormResultActionEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormResultStatusEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The result of a submitted contact form.
 *
 * A result with the {@see ContactFormResultStatusEnum::Error} status carries a fresh nonce: the
 * form wants another submission, e.g. with the code sent by e-mail.
 */
readonly class ContactFormResultMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_ACTION = 'action';
    public const string FIELD_MESSAGE = 'message';
    public const string FIELD_NONCE = 'nonce';
    public const string FIELD_DATA = 'data';
    public const string FIELD_MESSAGES = 'messages';
    /**-@#*/

    /**
     * @param ContactFormResultStatusEnum|null $status
     * @param ContactFormResultActionEnum|null $action how to present the result
     * @param string $message text to show the customer, in Polish
     * @param string $nonce the nonce for the next submission, empty after a success
     * @param ?ContactFormResultDataMessage $data what the form produced, e.g. the ticket number
     * @param ContactFormResultNoticeMessage[] $messages
     */
    public function __construct(
        /**
         * @var ContactFormResultStatusEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private ?ContactFormResultStatusEnum $status = null,

        /**
         * @var ContactFormResultActionEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_ACTION)]
        #[OmitWhenEmpty]
        private ?ContactFormResultActionEnum $action = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE)]
        #[OmitWhenEmpty]
        private string $message = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[OmitWhenEmpty]
        private string $nonce = '',

        /**
         * @var ?ContactFormResultDataMessage
         */
        #[Serializer\SerializedName(self::FIELD_DATA)]
        #[OmitWhenEmpty]
        private ?ContactFormResultDataMessage $data = null,

        /**
         * @var ContactFormResultNoticeMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGES)]
        #[OmitWhenEmpty]
        private array $messages = [],
    ) {}

    /**
     * @return ContactFormResultStatusEnum|null null for a value this version of the SDK doesn't know
     */
    public function getStatus(): ?ContactFormResultStatusEnum
    {
        return $this->status;
    }

    /**
     * @return ContactFormResultActionEnum|null null when absent or for a value this version of the
     *         SDK doesn't know
     */
    public function getAction(): ?ContactFormResultActionEnum
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @return ?ContactFormResultDataMessage
     */
    public function getData(): ?ContactFormResultDataMessage
    {
        return $this->data;
    }

    /**
     * @return ContactFormResultNoticeMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
