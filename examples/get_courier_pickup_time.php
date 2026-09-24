<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_courier_pickup_time — ask when a carrier could collect.
 *
 * The windows on offer for a given route, which is what to show before an order is placed rather
 * than guessing and having the collection rejected. The date and the time come back as separate
 * fields.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Courier\GetPickupTimeMessage;


require __DIR__ . '/bootstrap.php';

try {
    $slots = polkurier()->courier()->getCourierPickupTime(new GetPickupTimeMessage(
        courier: 'UPS',
        shipFrom: '61-001',
        shipTo: '00-001',
        parcel: ShipmentTypeEnum::Box,
    ));

    foreach ($slots as $slot) {
        foreach ($slot->getTime() as $window) {
            \printf(
                "%s  %s–%s\n",
                $slot->getPickupDate()->format('Y-m-d'),
                $window->getTimeFrom()->format('H:i'),
                $window->getTimeTo()->format('H:i')
            );
        }
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

