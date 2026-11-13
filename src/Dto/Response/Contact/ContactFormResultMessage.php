<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Contact\ContactFormResultActionEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormResultStatusEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The result of a submitted contact form.
 *
 * A result with the {@see ContactFormResultStatusEnum::Error} status carries a fresh nonce: the
 * form wants another submission, e.g. with the code sent by e-mail.
 */
final readonly class ContactFormResultMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var ContactFormResultStatusEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public ?ContactFormResultStatusEnum $status = null,

        /**
         * @var ContactFormResultActionEnum|null null when absent or for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_ACTION)]
        #[OmitWhenEmpty]
        public ?ContactFormResultActionEnum $action = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGE)]
        #[OmitWhenEmpty]
        public string $message = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[OmitWhenEmpty]
        public string $nonce = '',

        /**
         * @var ?ContactFormResultDataMessage
         */
        #[Serializer\SerializedName(self::FIELD_DATA)]
        #[OmitWhenEmpty]
        public ?ContactFormResultDataMessage $data = null,

        /**
         * @var ContactFormResultNoticeMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGES)]
        #[OmitWhenEmpty]
        public array $messages = [],

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
