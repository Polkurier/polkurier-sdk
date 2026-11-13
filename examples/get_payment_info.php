<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_payment_info — check what became of a payment.
 *
 * Poll after register_payment, using the transaction id it returned, to find out whether the
 * provider settled it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$transactionId = arguments()[0] ?? '1234567';

try {
    $info = polkurier()->payments()->getPaymentInfo(
        new GetPaymentInfoMessage(transactionId: $transactionId)
    );

    \printf("Transaction %s is %s\n", $info->transactionId, $info->state);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

