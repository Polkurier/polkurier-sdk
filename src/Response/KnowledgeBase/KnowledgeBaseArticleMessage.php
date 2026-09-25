<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A knowledge base article with its body and attachments.
 *
 * The content is an HTML fragment in Polish. Embed it in a page instead of showing it as text.
 */
readonly class KnowledgeBaseArticleMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    /**
     * @param string $id the article's slug
     * @param string $title
     * @param string $content HTML fragment, in Polish
     * @param KnowledgeBaseFileMessage[] $files attachments, if any
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        private string $title = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        private string $content = '',

        /**
         * @var KnowledgeBaseFileMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        private array $files = [],
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return KnowledgeBaseFileMessage[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
