<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\DecodesFileTrait;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A document attached to an order.
 *
 * The file is base64-encoded. {@see self::getDecodedFile()} returns the raw bytes. The hash
 * identifies the content and stays the same across downloads.
 */
final readonly class OrderFileMessage implements MessageInterface, ParsedResponseInterface
{
    use DecodesFileTrait;
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
     * @param ?\DateTimeInterface $dateCreated when the document was attached, null if unknown
     * @param string $file base64-encoded contents
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private string $type = '',

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_SIZE)]
        private int $size = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        private string $hash = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateCreated = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
