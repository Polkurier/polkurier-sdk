<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\KnowledgeBase;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A knowledge base article with its body and attachments.
 *
 * The content is an HTML fragment in Polish, meant to be embedded in a page rather than shown as
 * text.
 *
 * @api
 */
final readonly class KnowledgeBaseArticleMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

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
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        #[Serializer\SerializedName(self::FIELD_TITLE)]
        public string $title = '',

        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        /**
         * @var KnowledgeBaseFileMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        public array $files = [],

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
