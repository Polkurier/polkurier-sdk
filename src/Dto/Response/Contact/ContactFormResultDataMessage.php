<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * What a submitted contact form produced. Each field is present only for the forms that set it.
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
     * What separates a `data:` URL's media type from its base64 contents.
     */
    protected const string DATA_URL_MARKER = ';base64,';

    /**
     * @param ?string $ticketNumber the ticket the form opened
     * @param bool $requireAuthCode the form needs the code sent by e-mail: fill in its
     *        {@see \Polkurier\Webservice\Enum\Contact\ContactFormFieldTypeEnum::AuthCode} field
     * @param ?string $file a generated document, as a base64 `data:` URL
     * @param ?string $fileName the document's file name
     */
    public function __construct(
        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_TICKET_NUMBER)]
        #[OmitWhenEmpty]
        private ?string $ticketNumber = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_AUTH_CODE)]
        #[OmitWhenEmpty]
        private bool $requireAuthCode = false,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[OmitWhenEmpty]
        private ?string $file = null,

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_FILE_NAME)]
        #[OmitWhenEmpty]
        private ?string $fileName = null,
    ) {}

    /**
     * @return ?string
     */
    public function getTicketNumber(): ?string
    {
        return $this->ticketNumber;
    }

    /**
     * @return bool
     */
    public function isRequireAuthCode(): bool
    {
        return $this->requireAuthCode;
    }

    /**
     * @return ?string
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * The document's raw bytes, ready to write to disk or stream to a browser.
     *
     * @return ?string null without a document, empty when it isn't a valid base64 `data:` URL
     */
    public function getDecodedFile(): ?string
    {
        if ($this->file === null) {
            return null;
        }
        $marker = \strpos($this->file, self::DATA_URL_MARKER);

        return $marker === false
            ? ''
            : (string)\base64_decode(\substr($this->file, $marker + \strlen(self::DATA_URL_MARKER)), true);
    }

    /**
     * @return ?string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }
}
