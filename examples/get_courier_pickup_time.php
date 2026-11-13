<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_courier_pickup_time — ask when a carrier could collect.
 *
 * Up to 5 coming days the carrier can collect on from a postcode, each with its time windows, to
 * offer before an order is placed. Carriers without windows return days with an empty list, which
 * print nothing here.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;


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
                $slot->getPickupDate()?->format('Y-m-d'),
                $window->getTimeFrom()?->format('H:i'),
                $window->getTimeTo()?->format('H:i')
            );
        }
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

