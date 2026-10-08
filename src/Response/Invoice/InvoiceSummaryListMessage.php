<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\DecodesFile;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An invoice's itemised summary, as a file.
 *
 * The file is base64-encoded. {@see self::getDecodedFile()} returns the raw bytes. The filename is
 * the one the API suggests, and you can use it as-is in a download header.
 */
readonly class InvoiceSummaryListMessage implements MessageInterface
{
    use DecodesFile;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILENAME = 'filename';
    public const string FIELD_FILE_TYPE = 'type';
    /**-@#*/

    /**
     * @param string $file base64-encoded file
     * @param string $filename suggested filename
     * @param string $fileType media type of the file, e.g. `application/pdf` or `text/csv`
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        private string $filename = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE_TYPE)]
        private string $fileType = '',
    ) {}

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string the media type, e.g. `application/pdf` or `text/csv`
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }
}
