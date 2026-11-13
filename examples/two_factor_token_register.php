<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * two_factor_token_register — ask for a confirmation code.
 *
 * The first step of an operation that needs two-factor confirmation. The namespace says which
 * operation the code is for, and a code issued for one will not confirm another. The answer says
 * where the code went, when it expires, and the ways it can be sent.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example sends a real confirmation code. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $token = polkurier()->twoFactorToken()->twoFactorTokenRegister(
        new RegisterMessage(namespace: TwoFactorNamespaceEnum::ChangeBankAccountNumber)
    );

    \printf("Code sent to %s, valid until %s\n",
        $token->transportMethod->name ?? '(unknown)',
        $token->expiresAt?->format('H:i'));
    \printf("Token: %s\n", $token->id);

    foreach ($token->availableMethods as $method) {
        \printf("Can send it to %s (method id %s)\n", $method->name, $method->id);
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

