<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Limits the API applies to support ticket attachments.
 */
readonly class TicketLimitsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_MAX_FILE_SIZE = 'max_file_size';
    public const string FIELD_MAX_IMAGE_SIZE = 'max_image_size';
    public const string FIELD_MAX_FILES_COUNT = 'max_files_count';
    /**-@#*/

    /**
     * @param int $maxFileSize largest accepted attachment, in bytes
     * @param int $maxImageSize largest accepted image, in bytes
     * @param int $maxFilesCount most attachments allowed on one message
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_FILE_SIZE)]
        private int $maxFileSize = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_IMAGE_SIZE)]
        private int $maxImageSize = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_FILES_COUNT)]
        private int $maxFilesCount = 0,
    ) {}

    /**
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * @return int
     */
    public function getMaxImageSize(): int
    {
        return $this->maxImageSize;
    }

    /**
     * @return int
     */
    public function getMaxFilesCount(): int
    {
        return $this->maxFilesCount;
    }
}
