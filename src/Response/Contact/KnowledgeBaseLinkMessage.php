<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A help article or category a contact topic suggests.
 *
 * The id is the entry's slug. An article's is the one
 * {@see \Polkurier\Webservice\Api\Service\KnowledgeBaseServiceInterface::getArticle()} takes.
 */
readonly class KnowledgeBaseLinkMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TITLE = 'title';
    /**-@#*/

    /**
     * @param string $id the entry's slug
     * @param string $title display title, in Polish
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
    ) {}

    /**
     * @return string the entry's slug
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
}
