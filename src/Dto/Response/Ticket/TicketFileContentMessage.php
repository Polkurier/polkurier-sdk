<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\DecodesFileTrait;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A ticket attachment with its contents.
 *
 * The file is base64-encoded. {@see self::getDecodedFile()} returns the raw bytes.
 */
final readonly class TicketFileContentMessage implements MessageInterface, ParsedResponseInterface
{
    use DecodesFileTrait;
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_FILE = 'file';
    public const string FIELD_DATE_CREATED = 'date_created';
    public const string FIELD_HASH = 'hash';
    /**-@#*/

    /**
     * @param string $name filename
     * @param string $type media type
     * @param int $size size in bytes
     * @param string $file base64-encoded contents
     * @param ?\DateTimeInterface $dateCreated when it was attached
     * @param string $hash SHA-256 of the contents, in hex
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
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateCreated = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        private string $hash = '',

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
    public function getFile(): string
    {
        return $this->file;
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
    public function getHash(): string
    {
        return $this->hash;
    }
}
