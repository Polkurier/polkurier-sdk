<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * order_valuation_v2 + create_order — price an order, then place it.
 *
 * The two methods share one payload, which does not mean both will accept it. A successful quote
 * is a price, not a guarantee that the same payload will be accepted for creation, so this example
 * does both in turn rather than treating the quote as a dry run.
 *
 * Nothing is placed unless you pass --confirm, so the script is safe to run as-is.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\Order\PickupMessage;

require __DIR__ . '/bootstrap.php';

$order = new CreateOrderMessage(
    // Carrier codes come from available_carriers; see examples/available_carriers.php.
    courier: 'UPS',
    shipmentType: ShipmentTypeEnum::Box,
    packs: [
        new PackMessage(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
    ],
    sender: new OrderAddressMessage(
        person: 'Jan Kowalski',
        street: 'Długa',
        city: 'Poznań',
        postcode: '61-001',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        company: 'Acme sp. z o.o.',
        houseNumber: '12',
    ),
    recipient: new OrderAddressMessage(
        person: 'Anna Nowak',
        street: 'Krótka',
        city: 'Warszawa',
        postcode: '00-001',
        email: 'anna.nowak@example.com',
        phone: '600700800',
        houseNumber: '5',
        flatNumber: '3',
    ),
    // Declared value in PLN. Zero means no insurance.
    insurance: 500.0,
    // Some carriers need a collection date, some also a time window: available_carriers says which.
    pickup: new PickupMessage(
        pickupDate: new DateTimeImmutable('+1 weekday'),
        pickupTimeFrom: new DateTimeImmutable('10:00'),
        pickupTimeTo: new DateTimeImmutable('14:00'),
    ),
    courierService: [AdditionalServiceEnum::SmsOnDispatch],
    // Keep it short: see CreateOrderMessage::DESCRIPTION_MAX_LENGTH, and carriers may be stricter.
    description: 'Books',
    // Your own ids for the order, to find it again with get_orders, e.g. after a timeout.
    externalApplicationId: '6f1c2b9e-0d4a-4c3e-9b8f-1a2b3c4d5e6f',
    externalOrderId: 'EXAMPLE-' . \date('YmdHis'),
);

$sdk = polkurier();

try {
    $quote = null;
    foreach ($sdk->orders()->orderValuation($order) as $valuation) {
        if ($valuation->getServiceCode() === $order->getCourier()) {
            $quote = $valuation;
        }
    }

    if ($quote === null || !$quote->isShipment()) {
        echo "{$order->getCourier()} gives no quote for this shipment.\n";
        exit(1);
    }

    \printf("Quote: %.2f PLN net, %.2f PLN gross.\n", $quote->getNetPrice(), $quote->getGrossPrice());

    if (!hasFlag('--confirm')) {
        echo "\nRe-run with --confirm to place this order.\n";
        exit(0);
    }

    $created = $sdk->orders()->createOrder($order);

    \printf(
        "\nOrder %s placed. Waybills: %s. Gross: %.2f PLN\n",
        $created->getOrderNumber(),
        \implode(', ', $created->getLabel()) ?: '(pending)',
        $created->getPriceGross()
    );
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
