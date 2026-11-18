<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An invoice document as the API returns it.
 *
 * The filename is the one the API suggests, and you can use it as-is in a download header.
 *
 * @api
 */
final readonly class InvoiceDocumentMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILENAME = 'filename';
    public const string FIELD_FILETYPE = 'filetype';
    /**-@#*/

    /**
     * @param string $file the document's bytes
     * @param string $filename suggested filename
     * @param string $filetype media type of the document
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * The file's bytes, decoded from the base64 the API sends.
         *
         * @var string empty when the API sends none
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[Base64]
        public string $file = '',

        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        public string $filename = '',

        #[Serializer\SerializedName(self::FIELD_FILETYPE)]
        public string $filetype = '',

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
