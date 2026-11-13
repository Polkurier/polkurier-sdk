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
 * An invoice's itemised summary, as a file.
 *
 * The filename is the one the API suggests, and you can use it as-is in a download header.
 */
final readonly class InvoiceSummaryListMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILENAME = 'filename';
    public const string FIELD_FILE_TYPE = 'type';
    /**-@#*/

    /**
     * @param string $file the file's bytes
     * @param string $filename suggested filename
     * @param string $fileType media type of the file, e.g. `application/pdf` or `text/csv`
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

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILENAME)]
        public string $filename = '',

        /**
         * @var string the media type, e.g. `application/pdf` or `text/csv`
         */
        #[Serializer\SerializedName(self::FIELD_FILE_TYPE)]
        public string $fileType = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
