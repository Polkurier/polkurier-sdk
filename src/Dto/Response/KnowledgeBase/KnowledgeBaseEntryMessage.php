<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\KnowledgeBase;

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
 * The id is an opaque key that parent ids refer to. An article's slug is what
 * {@see \Polkurier\Webservice\Api\Service\KnowledgeBaseServiceInterface::getArticle()} takes.
 */
final readonly class KnowledgeBaseEntryMessage implements MessageInterface
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
     * @param string $id opaque key of the entry
     * @param string|null $parentId id of the parent category, null at the top level
     * @param string $slug URL-friendly name, which getArticle() takes for an article
     * @param string $title display title, in Polish
     * @param KnowledgeBaseEntryTypeEnum|null $type whether this is an article or a category
     * @param string $url public web address of the entry
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_PARENT_ID)]
        #[OmitWhenEmpty]
        public ?string $parentId = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SLUG)]
        public string $slug = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        public string $title = '',

        /**
         * @var KnowledgeBaseEntryTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?KnowledgeBaseEntryTypeEnum $type = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_URL)]
        public string $url = '',
    ) {}
}
