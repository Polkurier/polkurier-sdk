<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A file attached to a new ticket or a reply.
 *
 * The API accepts a file by the extension of its name, so give it a real one: png, pdf, jpg, jpeg,
 * gif, doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, csv or txt. A refused extension is reported
 * under `files[type]`. A file can't be empty and can be up to 10 MB, or 20 MB when its media type
 * is an image. A message can carry up to 5 files.
 *
 * The API stores the file under a name of its own, with a suffix added. The timeline entry's files
 * give that name, and it is the one
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getTicketMessageFile()} takes.
 */
readonly class TicketAttachmentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * The allowed extensions of a file's name.
     */
    public const array ALLOWED_EXTENSIONS = [
        'png', 'pdf', 'jpg', 'jpeg', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods',
        'odp', 'csv', 'txt',
    ];

    /**
     * @param string $name the file name, with one of {@see self::ALLOWED_EXTENSIONS}
     * @param string $file the contents, base64-encoded
     * @param string $type media type, e.g. `image/png`. It decides the size limit: 20 MB for an
     *        image, 10 MB otherwise
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        #[Validate\NotBlank]
        private string $name,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[Validate\NotBlank]
        private string $file,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[Validate\NotBlank]
        private string $type,
    ) {}

    /**
     * Builds an attachment from the raw contents, encoding them.
     *
     * @param string $name the file name, with one of {@see self::ALLOWED_EXTENSIONS}
     * @param string $contents the raw bytes
     * @param string $type media type, e.g. `image/png`
     *
     * @return self
     */
    public static function fromContents(string $name, string $contents, string $type): self
    {
        return new self($name, \base64_encode($contents), $type);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string base64-encoded
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
