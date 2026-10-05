<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\KnowledgeBase\KnowledgeBaseEntryTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One node of the knowledge base tree: a category or an article.
 *
 * The index is a flat list of these. The hierarchy comes from the parent id, not from nesting, so to
 * build a tree you group the entries by it yourself. A null parent marks a top-level entry.
 *
 * The id is the entry's slug, which is what
 * {@see \Polkurier\Webservice\Api\Service\KnowledgeBaseServiceInterface::getArticle()} takes.
 */
readonly class KnowledgeBaseEntryMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_PARENT_ID = 'parent_id';
    public const string FIELD_SLUG = 'slug';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_URL = 'url';
    /**-@#*/

    /**
     * @param string $id the entry's slug, and its identifier
     * @param ?string $parentId slug of the parent entry, null at the top level
     * @param string $slug URL-friendly name
     * @param string $title display title, in Polish
     * @param KnowledgeBaseEntryTypeEnum|null $type whether this is an article or a category
     * @param string $url public web address of the entry
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_PARENT_ID)]
        #[OmitWhenEmpty]
        private ?string $parentId = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SLUG)]
        private string $slug = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        private string $title = '',

        /**
         * @var KnowledgeBaseEntryTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?KnowledgeBaseEntryTypeEnum $type = KnowledgeBaseEntryTypeEnum::Article,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_URL)]
        private string $url = '',
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ?string
     */
    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return KnowledgeBaseEntryTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?KnowledgeBaseEntryTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
