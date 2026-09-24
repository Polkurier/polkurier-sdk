<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Names the article to fetch, by its slug.
 */
readonly class GetKnowledgeBaseArticleMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    /**-@#*/

    /**
     * @param string $id the article's slug, as the index reports it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Validate\NotBlank]
        private string $id,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
