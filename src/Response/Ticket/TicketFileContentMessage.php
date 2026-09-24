<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A ticket attachment with its contents.
 *
 * The file is base64-encoded; use {@see self::getDecodedFile()} for the raw bytes.
 */
readonly class TicketFileContentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * @param string $name filename
     * @param string $type media type
     * @param int $size size in bytes
     * @param string $file base64-encoded contents
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
     * The attachment's raw bytes, ready to write to disk or stream to a browser.
     *
     * @return string
     */
    public function getDecodedFile(): string
    {
        return (string)\base64_decode($this->file, true);
    }
}
