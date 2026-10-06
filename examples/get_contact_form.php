<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_contact_form — read the form a contact topic expects.
 *
 * Each topic defines its own fields, so there is no fixed shape: the answer is decoded JSON
 * describing what to render. It also carries the form id and nonce that save_contact_form must
 * echo back.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Contact\GetContactFormMessage;


require __DIR__ . '/bootstrap.php';

$category = arguments()[0] ?? 'topic-id-from-get_contact_topics';

try {
    dumpResponse(polkurier()->tickets()->getContactForm(
        new GetContactFormMessage(category: $category)
    ));
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

