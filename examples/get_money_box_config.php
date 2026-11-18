<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_money_box_config — read where collected cash-on-delivery money is sent.
 *
 * The standing arrangement: whether payouts go to a bank account or by post, how the fee is paid,
 * and the details they are sent to. Read this before offering a payout: it lists the fee payment
 * types the account can choose for create_money_box_payout.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $config = polkurier()->moneyBox()->getMoneyBoxConfig();

    \printf("Payout type: %s\n", $config->payoutType->value ?? 'unknown');
    \printf("Fee paid as: %s\n", $config->feePayment->value ?? 'unknown');
    \printf("IBAN:        %s\n", $config->bankAccountNumber);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

