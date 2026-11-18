<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\Base64DataUrl;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a submitted contact form produced. Each field is present only for the forms that set it.
 *
 * @api
 */
final readonly class ContactFormResultDataMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TICKET_NUMBER = 'ticket_number';
    public const string FIELD_REQUIRE_AUTH_CODE = 'require_auth_code';
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILE_NAME = 'fileName';
    /**-@#*/

    /**
     * @param string|null $ticketNumber the ticket the form opened
     * @param bool $requireAuthCode the form needs the code sent by e-mail: fill in its
     *        {@see \Polkurier\Webservice\Enum\Contact\ContactFormFieldTypeEnum::AuthCode} field
     * @param string|null $file a generated document's bytes
     * @param string|null $fileName the document's file name
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_TICKET_NUMBER)]
        public ?string $ticketNumber = null,

        #[Serializer\SerializedName(self::FIELD_REQUIRE_AUTH_CODE)]
        public bool $requireAuthCode = false,

        /**
         * A generated document's bytes, decoded from the base64 `data:` URL the API sends.
         *
         * @var string|null null without a document
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[Base64DataUrl]
        public ?string $file = null,

        #[Serializer\SerializedName(self::FIELD_FILE_NAME)]
        public ?string $fileName = null,
    ) {}
}
