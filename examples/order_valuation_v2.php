<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * order_valuation_v2 — price a fully assembled order before placing it.
 *
 * Takes the same payload as create_order and answers with what each carrier that can carry the
 * shipment would charge, so an order can be quoted and then placed without rebuilding it. A
 * payload that quotes may still be rejected when placed. examples/create_order.php shows the two in
 * sequence.
 *
 * Pass a carrier's service code, e.g. DPD, to price the order for that carrier only.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;


require __DIR__ . '/bootstrap.php';

$order = new CreateOrderMessage(
    courier: 'UPS',
    shipmentType: ShipmentTypeEnum::Box,
    packs: [new PackMessage(length: 30, width: 20, height: 15, weight: 2.5)],
    sender: new OrderAddressMessage(
        person: 'Jan Kowalski',
        street: 'Długa',
        city: 'Poznań',
        postcode: '61-001',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        houseNumber: '12',
    ),
    recipient: new OrderAddressMessage(
        person: 'Anna Nowak',
        street: 'Krótka',
        city: 'Warszawa',
        postcode: '00-001',
        email: 'anna.nowak@example.com',
        phone: '600700800',
        houseNumber: '3',
    ),
);

try {
    foreach (polkurier()->orders()->orderValuation($order, $argv[1] ?? null) as $quote) {
        if (!$quote->isShipment()) {
            continue;
        }

        \printf(
            "%-28s %8.2f net %8.2f gross%s\n",
            $quote->getServiceCode(),
            $quote->getNetPrice(),
            $quote->getGrossPrice(),
            $quote->isAvailable() ? '' : '  unavailable: ' . $quote->getUnavailableMessage(),
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

