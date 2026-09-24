<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
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

    \printf("Payout type: %s\n", $config->getPayoutType()->value);
    \printf("Fee paid by: %s\n", $config->getFeePayment()->value);
    \printf("IBAN:        %s\n", $config->getIban());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

