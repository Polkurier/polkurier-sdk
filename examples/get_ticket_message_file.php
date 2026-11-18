<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_ticket_message_file — download a file attached to a ticket message.
 *
 * The entry id and the file name both come from the ticket's timeline, which get_ticket_messages
 * prints. Use the name the API stored the file under, with its added suffix, not the name it was
 * sent with. The content arrives base64-encoded.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Ticket\GetTicketMessageFileMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$messageId = arguments()[0] ?? 'entry-id-from-get_ticket_messages';
$filename = arguments()[1] ?? 'stored-file-name-from-get_ticket_messages';

try {
    $file = polkurier()->tickets()->getTicketMessageFile(
        new GetTicketMessageFileMessage(messageId: $messageId, filename: $filename)
    );

    $path = \sys_get_temp_dir() . '/' . \basename($file->name);
    \file_put_contents($path, $file->file);

    \printf("Saved %s (%s, %d bytes)\n", $path, $file->type, $file->size);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

