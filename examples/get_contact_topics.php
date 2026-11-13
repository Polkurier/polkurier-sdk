<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_contact_topics — list the subjects a ticket can be raised under.
 *
 * A tree: each topic may have a parent, and some carry knowledge base articles that answer the
 * question without a ticket. Only topics whose `hasForm` is set accept a contact form.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->tickets()->getContactTopics() as $topic) {
        \printf(
            "%-8s %-44s %s\n",
            $topic->id,
            $topic->title,
            $topic->hasForm ? '(has a form)' : ''
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

