<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An attachment on a knowledge base article.
 *
 * The URL is a public download link and needs no credentials.
 *
 * @api
 */
final readonly class KnowledgeBaseFileMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_URL = 'url';
    /**-@#*/

    /**
     * @param string $id opaque file symbol
     * @param string $name filename as shown to the reader
     * @param string $type media type
     * @param int $size size in bytes
     * @param string $url public download link
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

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
        #[Serializer\SerializedName(self::FIELD_URL)]
        public string $url = '',
    ) {}
}
