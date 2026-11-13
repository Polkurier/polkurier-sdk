<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * is_multi_pickup_available — check whether a shipment can join a collective pickup.
 *
 * Ask before placing an order for a collective pickup, booked later with
 * create_multi_order_pickup. The answer depends on the shipment, chiefly its carrier and parcels, so
 * ask for each one. Takes a carrier code as the first argument.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\MultiPickupAvailabilityRequestMessage;
use Polkurier\Webservice\Entity\Order\PackMessage;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $availability = polkurier()->orders()->isMultiPickupAvailable(new MultiPickupAvailabilityRequestMessage(
        courier: arguments()[0] ?? 'DPD',
        packs: [new PackMessage(length: 40, width: 30, height: 20, weight: 0.55)],
        senderPostCode: '31-042',
        shipmentType: ShipmentTypeEnum::Box,
    ));

    echo $availability->available
        ? "This shipment can join a collective pickup.\n"
        : "A collective pickup is not available for this shipment.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
