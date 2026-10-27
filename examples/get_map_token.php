<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_map_token — get a short-lived token for the pickup-point map.
 *
 * The map widget authenticates with its own token rather than with the API token, so that the API
 * token never reaches the browser. The token expires 4 hours after it's issued.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $token = polkurier()->geo()->getMapToken();

    \printf("Map token: %s\n", $token->getToken());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

