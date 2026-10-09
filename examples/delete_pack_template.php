<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * delete_pack_template — remove a parcel template.
 *
 * Templates are keyed by id alone. Deleting one does not touch any order that was built from it,
 * since nothing references a template after the fact.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Common\DeleteMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example deletes a real template. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$id = arguments()[0] ?? 'id-from-get_pack_templates';

try {
    $deleted = polkurier()->packTemplates()->deletePackTemplates(new DeleteMessage(id: $id));

    echo $deleted ? "Deleted {$id}.\n" : "Nothing was deleted.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

