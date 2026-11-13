<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_contact_form — read the form a contact topic expects.
 *
 * Each topic defines its own fields. This lists them with the names to answer them under, and the
 * choices of the drop-down fields. The form also carries the id and nonce that save_contact_form
 * must send back.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$category = arguments()[0] ?? 'topic-id-from-get_contact_topics';

try {
    $form = polkurier()->tickets()->getContactForm(new GetContactFormMessage(category: $category));

    if (!$form->hasForm) {
        echo "This topic has no form.\n";
        exit(0);
    }

    if ($form->webOnly) {
        echo "This form can only be filled in on the website: {$form->href}\n";
        exit(0);
    }

    echo "Form {$form->id}\n";
    foreach ($form->fields as $field) {
        if ($field->name === '') {
            continue;
        }

        \printf(
            "  %-24s %-10s %s%s\n",
            $field->name,
            $field->type->value ?? '?',
            \mb_strimwidth(\trim((string)\preg_replace('/\s+/u', ' ', \strip_tags($field->label))), 0, 60, '…'),
            $field->condition === '' ? '' : '  (conditional)',
        );
        foreach ($field->options as $option) {
            \printf("      %s = %s\n", $option->value, $option->label);
        }
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

