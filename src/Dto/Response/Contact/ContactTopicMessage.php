<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One topic a support ticket or a contact form can be raised under.
 *
 * Topics form a tree through the parent id. A topic can link to help articles, which you can show
 * before the customer opens a ticket.
 */
final readonly class ContactTopicMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_PARENT_ID = 'parent_id';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_HAS_FORM = 'has_form';
    public const string FIELD_KNOWLEDGE_BASE_ARTICLES = 'knowledge_base_articles';
    public const string FIELD_KNOWLEDGE_BASE_CATEGORIES = 'knowledge_base_categories';
    /**-@#*/

    /**
     * @param string $id the id to quote when raising a ticket or fetching the contact form
     * @param ?string $parentId parent topic, null at the top level
     * @param string $title display title, in Polish
     * @param string $content explanatory HTML fragment, in Polish
     * @param bool $hasForm whether the topic has a contact form
     * @param KnowledgeBaseLinkMessage[] $knowledgeBaseArticles help articles suggested for this topic
     * @param KnowledgeBaseLinkMessage[] $knowledgeBaseCategories help categories suggested for this topic
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_PARENT_ID)]
        #[OmitWhenEmpty]
        public ?string $parentId = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        public string $title = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_FORM)]
        public bool $hasForm = false,

        /**
         * @var KnowledgeBaseLinkMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_KNOWLEDGE_BASE_ARTICLES)]
        public array $knowledgeBaseArticles = [],

        /**
         * @var KnowledgeBaseLinkMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_KNOWLEDGE_BASE_CATEGORIES)]
        public array $knowledgeBaseCategories = [],
    ) {}
}
