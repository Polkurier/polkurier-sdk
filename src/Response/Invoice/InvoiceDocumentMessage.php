<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An invoice document as the API returns it.
 *
 * The file is base64-encoded; use {@see self::getDecodedFile()} for the raw bytes. The filename is
 * the one the API suggests and is safe to use verbatim in a download header.
 */
readonly class InvoiceDocumentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILENAME = 'filename';
    public const string FIELD_FILETYPE = 'filetype';
    public const string FIELD_INVOICE_NUMBER = 'faktura_nr';
    /**-@#*/

    /**
     * @param string $file base64-encoded document
     * @param string $filename suggested filename
     * @param string $filetype media type of the document
     * @param string $invoiceNumber the invoice number this document is for
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
        #[Serializer\SerializedName(self::FIELD_FILETYPE)]
        private string $filetype = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_INVOICE_NUMBER)]
        private string $invoiceNumber = '',
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
     * @return string
     */
    public function getFiletype(): string
    {
        return $this->filetype;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    /**
     * The document's raw bytes, ready to write to disk or stream to a browser.
     *
     * @return string
     */
    public function getDecodedFile(): string
    {
        return (string)\base64_decode($this->file, true);
    }
}
