<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_status — read where a shipment has got to.
 *
 * The current status always; the carrier's scan history only when tracking is asked for, which is
 * noticeably slower. Poll the plain form and fetch the history only when showing it to someone.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\GetStatusMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$orderNumber = arguments()[0] ?? '12345-678';

try {
    $status = polkurier()->orders()->getStatus(
        new GetStatusMessage(orderNo: $orderNumber, tracking: true)
    );

    \printf("%s: %s (%s)\n", $orderNumber, $status->getStatus(), $status->getStatusCode());

    foreach ($status->getTracking() as $event) {
        \printf(
            "  %-18s %-24s %s\n",
            $event->getStatusDate()?->format('Y-m-d H:i') ?? '-',
            $event->getLocation(),
            $event->getDescription()
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

