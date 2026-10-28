<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_knowledge_base_article — read one help article.
 *
 * The slug comes from the index or from a search. The content is HTML, and the article's
 * attachments come with it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\KnowledgeBase\GetKnowledgeBaseArticleMessage;


require __DIR__ . '/bootstrap.php';

$id = arguments()[0] ?? 'slug-from-get_knowledge_base_index';

try {
    $article = polkurier()->knowledgeBase()->getArticle(
        new GetKnowledgeBaseArticleMessage(id: $id)
    );

    \printf("%s\n\n%s\n", $article->getTitle(), \strip_tags($article->getContent()));
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

