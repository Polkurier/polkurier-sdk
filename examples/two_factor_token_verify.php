<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * two_factor_token_verify — check a confirmation code.
 *
 * Answers with a flag rather than an error, so a wrong code can be reported to the customer
 * without an exception. Verifying does not perform the guarded operation: pass the token and code
 * to that operation's own call.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$token = arguments()[0] ?? 'token-from-two_factor_token_register';
$code = arguments()[1] ?? '000000';

try {
    $validity = polkurier()->twoFactorToken()->twoFactorTokenVerify(
        new VerifyMessage(token: $token, code: $code)
    );

    echo $validity->isValid() ? "That code is correct.\n" : "That code is wrong.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

