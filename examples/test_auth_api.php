<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * test_auth_api — confirm a set of credentials works.
 *
 * The one method that takes its credentials as an argument rather than using the configured ones,
 * which is what makes it useful for checking a token a customer has just pasted in before storing
 * it. A wrong token throws an {@see AuthorizationException}.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;


require __DIR__ . '/bootstrap.php';

$login = (string)\getenv('POLKURIER_API_LOGIN');
$token = (string)\getenv('POLKURIER_API_TOKEN');

try {
    $confirmation = polkurier()->utils()->testAuthApi(
        new AuthorizationMessage(token: $token, login: $login)
    );

    \printf("Credentials for account %s are valid (%s).\n", $login, $confirmation->authorized ? 'yes' : 'no');
} catch (AuthorizationException $e) {
    \printf("Credentials for account %s were rejected: %s\n", $login, $e->getMessage());
    exit(1);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

