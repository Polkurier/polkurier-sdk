<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * save_contact_form — submit a completed contact form.
 *
 * The form id and nonce come from get_contact_form and tie the submission to the form that was
 * rendered, so fetch the form immediately before submitting rather than caching it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Contact\SaveContactFormMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example submits a real contact form. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$category = arguments()[0] ?? '1';

try {
    $saved = polkurier()->tickets()->saveContactForm(new SaveContactFormMessage(
        category: $category,
        formId: arguments()[1] ?? 'form-id-from-get_contact_form',
        nonce: arguments()[2] ?? 'nonce-from-get_contact_form',
    ));

    echo $saved ? "Form submitted.\n" : "The form was not accepted.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

