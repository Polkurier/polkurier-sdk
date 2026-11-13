<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * pickup_courier — check whether a carrier can collect on one day, and in which windows.
 *
 * Nothing is booked. A collection is booked with the order, through its pickup block, or for
 * several orders at once with create_multi_order_pickup.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$day = new DateTimeImmutable('+1 weekday');

try {
    $pickup = polkurier()->courier()->pickupCourier(new PickupMessage(
        courier: 'UPS',
        shipFrom: '61-001',
        parcel: ShipmentTypeEnum::Box,
        pickupDate: $day,
    ));

    if (!$pickup->isPickupDate()) {
        \printf("The carrier cannot collect on %s.\n", $day->format('Y-m-d'));
        exit(1);
    }

    \printf("The carrier can collect on %s.\n", $day->format('Y-m-d'));
    foreach ($pickup->getTime() as $window) {
        \printf(
            "  window offered: %s–%s\n",
            $window->getTimeFrom()?->format('H:i'),
            $window->getTimeTo()?->format('H:i'),
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

