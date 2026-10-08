<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_money_box_config — read where collected cash-on-delivery money is sent.
 *
 * The standing arrangement: whether payouts go to a bank account or by post, who pays the fee, and
 * the details they are sent to. Read this before offering a payout, since it decides which fields
 * create_money_box_payout has to carry.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $config = polkurier()->moneyBox()->getConfig();

    \printf("Payout type: %s\n", $config->getPayoutType()->value ?? 'unknown');
    \printf("Fee paid by: %s\n", $config->getFeePayment()->value ?? 'unknown');
    \printf("IBAN:        %s\n", $config->getIban());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

