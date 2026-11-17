<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A document attached to an order.
 *
 * The hash identifies the content and stays the same across downloads.
 *
 * @api
 */
final readonly class OrderFileMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_HASH = 'hash';
    public const string FIELD_DATE_CREATED = 'date_created';
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * @param string $name filename
     * @param string $type media type
     * @param int $size size in bytes
     * @param string $hash content hash, stable across downloads
     * @param \DateTimeInterface|null $dateCreated when the document was attached, null if unknown
     * @param string $file the file's bytes
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_SIZE)]
        public int $size = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        public string $hash = '',

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $dateCreated = null,

        /**
         * The file's bytes, decoded from the base64 the API sends.
         *
         * @var string empty when the API sends none
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[Base64]
        public string $file = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
