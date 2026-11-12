<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\DecodesFileTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An invoice document as the API returns it.
 *
 * The file is base64-encoded. {@see self::getDecodedFile()} returns the raw bytes. The filename is
 * the one the API suggests, and you can use it as-is in a download header.
 */
readonly class InvoiceDocumentMessage implements MessageInterface
{
    use DecodesFileTrait;

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
     * @param string $invoiceNumber not sent by the API, always empty
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
         *
         * @deprecated The API does not send this field; it is always empty.
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
     *
     * @deprecated The API does not send this field; it is always empty. The number is the one
     *             passed to {@see \Polkurier\Webservice\Api\Service\InvoicesServiceInterface::getInvoicePdf()}.
     */
    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }
}
