<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One topic a support ticket can be raised under.
 *
 * Topics form a shallow tree through the parent id. You can raise a ticket only under a topic with
 * `has_form` set. The others group topics and link to help articles, which you can show before the
 * customer opens a ticket.
 */
readonly class ContactTopicMessage implements MessageInterface
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
     * @param string $id the id to quote when raising a ticket
     * @param ?string $parentId parent topic, null at the top level
     * @param string $title display title, in Polish
     * @param string $content explanatory text, in Polish
     * @param bool $hasForm whether a ticket can be raised under this topic
     * @param array<int, array<string, mixed>> $knowledgeBaseArticles help articles suggested for this topic
     * @param array<int, array<string, mixed>> $knowledgeBaseCategories help categories suggested for this topic
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
        #[Serializer\SerializedName(self::FIELD_TITLE)]
        private string $title = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        private string $content = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_FORM)]
        private bool $hasForm = false,

        /**
         * @var array<int, array<string, mixed>>
         */
        #[Serializer\SerializedName(self::FIELD_KNOWLEDGE_BASE_ARTICLES)]
        private array $knowledgeBaseArticles = [],

        /**
         * @var array<int, array<string, mixed>>
         */
        #[Serializer\SerializedName(self::FIELD_KNOWLEDGE_BASE_CATEGORIES)]
        private array $knowledgeBaseCategories = [],
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
     * @return bool
     */
    public function isHasForm(): bool
    {
        return $this->hasForm;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getKnowledgeBaseArticles(): array
    {
        return $this->knowledgeBaseArticles;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getKnowledgeBaseCategories(): array
    {
        return $this->knowledgeBaseCategories;
    }
}
