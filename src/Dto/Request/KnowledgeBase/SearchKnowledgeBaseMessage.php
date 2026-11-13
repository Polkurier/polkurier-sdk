<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Free-text search across the knowledge base.
 */
final readonly class SearchKnowledgeBaseMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_QUERY = 'query';
    /**-@#*/

    /**
     * @param string $query the phrase to search for
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_QUERY)]
        #[Validate\NotBlank]
        private string $query,
    ) {}

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
