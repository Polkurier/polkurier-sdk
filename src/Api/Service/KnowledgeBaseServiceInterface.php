<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\KnowledgeBase\GetKnowledgeBaseArticleMessage;
use Polkurier\Webservice\Dto\Request\KnowledgeBase\SearchKnowledgeBaseMessage;
use Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseArticleMessage;
use Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseEntryMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Help articles from the Polkurier knowledge base.
 *
 * All content is in Polish. Article bodies are HTML fragments you can embed.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 *
 * @api
 */
interface KnowledgeBaseServiceInterface
{
    /**
     * Returns the knowledge base's categories and articles as a flat list.
     *
     * Each entry's parent id is the id of its category. Group on it to build a tree. A null parent
     * means a top-level entry.
     *
     * Only ARTICLE entries can be fetched with getArticle(). A category's slug returns not found.
     *
     * Endpoint: `get_knowledge_base_index`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_knowledge_base_index",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "1111111111111111111111111111111111111111",
     *             "parent_id": null,
     *             "slug": "integracje",
     *             "title": "Integracje",
     *             "type": "CATEGORY",
     *             "url": "https://polkurier.pl/baza-wiedzy/kategoria/integracje"
     *         },
     *         {
     *             "id": "2222222222222222222222222222222222222222",
     *             "parent_id": "1111111111111111111111111111111111111111",
     *             "slug": "api-pierwsze-kroki",
     *             "title": "API — pierwsze kroki",
     *             "type": "ARTICLE",
     *             "url": "https://polkurier.pl/baza-wiedzy/artykul/api-pierwsze-kroki"
     *         }
     *     ]
     * }
     * ```
     *
     * @return KnowledgeBaseEntryMessage[] payload:
     *         {@see \Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseEntryMessage}[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getIndex(): array;

    /**
     * Searches the knowledge base by free text.
     *
     * Returns up to 25 articles, best match first, in the same shape as getIndex(). There is no
     * relevance score.
     *
     * Endpoint: `search_knowledge_base`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "search_knowledge_base",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"query": "paczka"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "4444444444444444444444444444444444444444",
     *             "parent_id": "3333333333333333333333333333333333333333",
     *             "slug": "sposoby-pakowania-paczka-w-ruchu",
     *             "title": "Sposoby pakowania - Orlen Paczka",
     *             "type": "ARTICLE",
     *             "url": "https://polkurier.pl/baza-wiedzy/artykul/sposoby-pakowania-paczka-w-ruchu"
     *         }
     *     ]
     * }
     * ```
     *
     * @param SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage
     *
     * @return KnowledgeBaseEntryMessage[] payload:
     *         {@see \Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseEntryMessage}[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function search(SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage): array;

    /**
     * Fetches one article with its body and attachments.
     *
     * Pass the entry's `slug` from the index (not its `id`). It is sent in the request's `id` field.
     * Attachment URLs are public download links and need no credentials.
     *
     * Endpoint: `get_knowledge_base_article`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_knowledge_base_article",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "api-pierwsze-kroki"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "api-pierwsze-kroki",
     *         "title": "API — pierwsze kroki",
     *         "content": "<p>Aby rozpocząć…</p>",
     *         "files": [
     *             {
     *                 "id": "a1b2c3",
     *                 "name": "dokumentacja.pdf",
     *                 "type": "application/pdf",
     *                 "size": 254000,
     *                 "url": "https://polkurier.pl/files/download/a1b2c3"
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [id]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND",
     *  "response": "Nie znaleziono obiektu"}
     * ```
     * Thrown for an unknown slug, an unpublished article, or a category's slug.
     *
     * @param GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
     *
     * @return KnowledgeBaseArticleMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\KnowledgeBase\KnowledgeBaseArticleMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getArticle(
        GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
    ): KnowledgeBaseArticleMessage;
}
