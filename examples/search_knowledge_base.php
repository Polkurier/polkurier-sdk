<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * search_knowledge_base — search the help articles.
 *
 * Answers with the same entry shape the index uses, so a result can be rendered with the same
 * code. Pair it with get_knowledge_base_article to show what was found.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\KnowledgeBase\SearchKnowledgeBaseMessage;


require __DIR__ . '/bootstrap.php';

$query = arguments()[0] ?? 'paczkomat';

try {
    foreach (polkurier()->knowledgeBase()->search(new SearchKnowledgeBaseMessage(query: $query)) as $entry) {
        \printf("%-50s %s\n", $entry->getSlug(), $entry->getTitle());
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

