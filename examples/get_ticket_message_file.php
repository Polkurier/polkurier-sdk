<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_ticket_message_file — download a file attached to a ticket message.
 *
 * The message id and the file name both come from the ticket's timeline, so read the ticket first.
 * The content arrives base64-encoded.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Ticket\GetTicketMessageFileMessage;


require __DIR__ . '/bootstrap.php';

$messageId = arguments()[0] ?? '1';
$filename = arguments()[1] ?? 'zdjecie.jpg';

try {
    $file = polkurier()->tickets()->getTicketMessageFile(
        new GetTicketMessageFileMessage(messageId: $messageId, filename: $filename)
    );

    $path = \sys_get_temp_dir() . '/' . $file->getName();
    \file_put_contents($path, $file->getDecodedFile());

    \printf("Saved %s (%s, %d bytes)\n", $path, $file->getType(), $file->getSize());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

