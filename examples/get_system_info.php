<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_system_info — read the limits the API is currently applying.
 *
 * Security PIN lengths, the two-factor code length and resend window, ticket attachment limits,
 * the minimum coupon length, the verification transfer amount and the terms acceptance period. It
 * holds no account data, so call it once at start-up and cache it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $info = polkurier()->system()->getSystemInfo();

    \printf("Verification transfer  %.2f PLN\n", $info->verificationTransferAmountPln);
    \printf("Terms acceptance       %d days\n", $info->regulationsAcceptancePeriod);
    \printf("Coupon code            at least %d characters\n", $info->coupons->minLength);
    \printf(
        "Security PIN           %d to %d characters\n",
        $info->security->userPinMinLength,
        $info->security->userPinMaxLength
    );
    \printf(
        "Two-factor code        %d characters, resent after %d seconds\n",
        $info->security->twoFactorCodeLength,
        $info->security->twoFactorResendThrottle
    );
    \printf(
        "Ticket attachments     %d files, %d bytes each, %d bytes for an image\n",
        $info->tickets->maxFilesCount,
        $info->tickets->maxFileSize,
        $info->tickets->maxImageSize
    );

    foreach ($info->features as $feature => $enabled) {
        \printf("  %-20s %s\n", $feature, $enabled ? 'on' : 'off');
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

