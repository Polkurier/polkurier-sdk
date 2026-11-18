<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Limits the API applies to support ticket attachments.
 *
 * @api
 */
final readonly class TicketLimitsMessage implements MessageInterface
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
        #[Serializer\SerializedName(self::FIELD_MAX_FILE_SIZE)]
        public int $maxFileSize = 0,

        #[Serializer\SerializedName(self::FIELD_MAX_IMAGE_SIZE)]
        public int $maxImageSize = 0,

        #[Serializer\SerializedName(self::FIELD_MAX_FILES_COUNT)]
        public int $maxFilesCount = 0,
    ) {}
}
