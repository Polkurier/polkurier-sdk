<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * register_payment — start an online payment.
 *
 * Answers with a transaction id and an access token for the payment provider's flow. The mode
 * decides what is being paid for — topping up the prepaid balance, settling invoices, or paying
 * for specific orders.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example starts a real payment. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $payment = polkurier()->payments()->registerPayment(
        new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid, amount: 100.0)
    );

    \printf("Transaction %s is %s\n", $payment->getTransactionId(), $payment->getState());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

