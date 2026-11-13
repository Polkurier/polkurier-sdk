<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * save_contact_form — submit a completed contact form.
 *
 * The form id and nonce come from get_contact_form and tie the submission to the form that was
 * rendered, so this fetches the form immediately before submitting rather than caching it.
 *
 * Takes the topic id from get_contact_topics, then the answers as name=value pairs keyed by the
 * form's field names, e.g. `name="Jan Kowalski" phone=500600700 rules_accept=1`. A rejected
 * submission lists the fields to fix; a form that wants the e-mailed code asks for another
 * submission.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Exception\WebserviceException;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example submits a real contact form. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$arguments = arguments();
$category = \array_shift($arguments) ?? 'topic-id-from-get_contact_topics';

$answers = [];
foreach ($arguments as $pair) {
    [$name, $value] = \array_pad(\explode('=', $pair, 2), 2, '');
    $answers[$name] = $value;
}

try {
    $form = polkurier()->tickets()->getContactForm(new GetContactFormMessage(category: $category));

    $saved = polkurier()->tickets()->saveContactForm(new SaveContactFormMessage(
        category: $category,
        formId: $form->getId(),
        nonce: $form->getNonce(),
        fields: $answers,
    ));

    if ($saved->getData()?->isRequireAuthCode()) {
        echo "Enter the code sent by e-mail and submit again with nonce {$saved->getNonce()}.\n";
        exit(0);
    }

    echo $saved->getMessage(), "\n";
    $ticket = $saved->getData()?->getTicketNumber();
    if ($ticket !== null) {
        echo "Ticket {$ticket}\n";
    }
} catch (WebserviceException $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    foreach ($e->getErrorMessage()->getValidationErrors() ?? [] as $field => $messages) {
        \fwrite(STDERR, "  $field: " . \implode(', ', $messages) . "\n");
    }
    exit(1);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
