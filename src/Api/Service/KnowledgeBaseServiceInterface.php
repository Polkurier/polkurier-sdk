<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\KnowledgeBase\GetKnowledgeBaseArticleMessage;
use Polkurier\Webservice\Request\KnowledgeBase\SearchKnowledgeBaseMessage;
use Polkurier\Webservice\Response\KnowledgeBase\KnowledgeBaseArticleMessage;
use Polkurier\Webservice\Response\KnowledgeBase\KnowledgeBaseEntryMessage;

/**
 * The customer-facing help content.
 *
 * All content is Polish, and article bodies are HTML fragments intended to be embedded.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface KnowledgeBaseServiceInterface
{
    /**
     * The whole knowledge base as a flat index of categories and articles.
     *
     * Flat rather than nested: the hierarchy is expressed through each entry's parent id, so
     * building a tree means grouping on it. A null parent marks a top-level entry.
     *
     * Only entries of type ARTICLE can be fetched with getArticle(); asking for a category's slug
     * finds nothing.
     *
     * Endpoint: `get_knowledge_base_index`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_knowledge_base_index",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
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
     *             "url": "https://polkurier.pl/baza-wiedzy/api-pierwsze-kroki"
     *         }
     *     ]
     * }
     * ```
     *
     * @return KnowledgeBaseEntryMessage[] payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\KnowledgeBase\KnowledgeBaseEntryMessage}[]
     */
    public function getIndex(): array;

    /**
     * Searches the knowledge base by free text.
     *
     * Returns index entries in the same shape as getIndex(), so a hit can be resolved to an
     * article without a second lookup. No relevance score is reported — the order is the API's
     * own ranking.
     *
     * A search matching nothing is a success with an empty list, not an error.
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
     *             "id": "2222222222222222222222222222222222222222",
     *             "parent_id": "3333333333333333333333333333333333333333",
     *             "slug": "sposoby-pakowania-paczka-w-ruchu",
     *             "title": "Sposoby pakowania - Orlen Paczka",
     *             "type": "ARTICLE",
     *             "url": "https://polkurier.pl/baza-wiedzy/sposoby-pakowania-paczka-w-ruchu"
     *         }
     *     ]
     * }
     * ```
     *
     * @param SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage
     *
     * @return KnowledgeBaseEntryMessage[] payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\KnowledgeBase\KnowledgeBaseEntryMessage}[]
     */
    public function search(SearchKnowledgeBaseMessage $searchKnowledgeBaseMessage): array;

    /**
     * Fetches one article with its body and attachments.
     *
     * Addressed by slug, which is what the index reports as the entry's id. Attachment URLs are
     * public download links that need no credentials.
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
     *                 "url": "https://polkurier.pl/download/a1b2c3"
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
     * Raised for an unknown slug, for an unpublished article, and for a category's slug — a
     * category is not fetchable as an article.
     *
     * @param GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
     *
     * @return KnowledgeBaseArticleMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\KnowledgeBase\KnowledgeBaseArticleMessage}
     */
    public function getArticle(
        GetKnowledgeBaseArticleMessage $getKnowledgeBaseArticleMessage
    ): KnowledgeBaseArticleMessage;
}
