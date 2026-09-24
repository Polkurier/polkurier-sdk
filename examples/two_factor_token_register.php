<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * two_factor_token_register — ask for a confirmation code.
 *
 * The first step of any operation the API guards. The namespace says which operation the code is
 * for, and a code issued for one will not confirm another. The answer carries the number it was
 * sent to and when it expires.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\TwoFactorToken\RegisterMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example sends a real confirmation code. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $token = polkurier()->twoFactorToken()->twoFactorTokenRegister(
        new RegisterMessage(namespace: TwoFactorNamespaceEnum::ChangeIban)
    );

    \printf("Code sent to %s, valid until %s\n",
        $token->getPhoneNumber(),
        $token->getExpiresAt()->format('H:i'));
    \printf("Token: %s\n", $token->getId());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

