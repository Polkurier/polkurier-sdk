<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_knowledge_base_index — list the help articles and their categories.
 *
 * A flat list with parent ids rather than a nested tree, so build the hierarchy yourself if you
 * need one. Entries are either categories or articles; the type says which.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->knowledgeBase()->getIndex() as $entry) {
        \printf("%-8s %-12s %s\n", $entry->getId(), $entry->getType()->value, $entry->getTitle());
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

