<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * pickup_courier — book a collection that is not tied to one order.
 *
 * A standing collection request, for handing over whatever is ready when the courier arrives
 * rather than a specific consignment. An order placed with its own pickup block does not need
 * this. Which carriers accept it, and how far ahead, varies — available_carriers reports it.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Courier\PickupMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example books a real collection. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$day = new DateTimeImmutable('+1 weekday');

try {
    $pickup = polkurier()->courier()->pickupCourier(new PickupMessage(
        courier: 'UPS',
        shipFrom: '61-001',
        parcel: ShipmentTypeEnum::Box,
        pickupDate: $day,
    ));

    if (!$pickup->isPickupDate()) {
        \printf("The carrier refused a collection on %s.\n", $day->format('Y-m-d'));
        exit(1);
    }

    \printf("Collection booked for %s.\n", $day->format('Y-m-d'));
    foreach ($pickup->getTime() as $window) {
        \printf("  window offered: %s–%s\n", $window->getTimeFrom()->format('H:i'), $window->getTimeTo()->format('H:i'));
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

