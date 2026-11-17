<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * order_valuation_v2 + create_order — price an order, then place it.
 *
 * The two methods share one payload, but a payload that gets a quote can still be rejected when
 * placed, so this example does both in turn.
 *
 * Nothing is placed unless you pass --confirm, so the script is safe to run as-is.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\CreateOrderMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Entity\Order\Pickup;
use Polkurier\Webservice\Enum\Order\AdditionalServiceEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

$order = new Order(
    // Carrier codes come from available_carriers; see examples/available_carriers.php.
    courier: 'UPS',
    shipmentType: ShipmentTypeEnum::Box,
    packs: [
        new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard),
    ],
    sender: new Address(
        person: 'Jan Kowalski',
        street: 'Długa',
        city: 'Poznań',
        postcode: '61-001',
        email: 'jan.kowalski@example.com',
        phone: '500600700',
        company: 'Acme sp. z o.o.',
        houseNumber: '12',
    ),
    recipient: new Address(
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
    pickup: new Pickup(
        pickupDate: new DateTimeImmutable('+1 weekday'),
        pickupTimeFrom: new DateTimeImmutable('10:00'),
        pickupTimeTo: new DateTimeImmutable('14:00'),
    ),
    courierService: [AdditionalServiceEnum::SmsOnDispatch],
    // Required to place the order. Cut to fit {@see Order::DESCRIPTION_MAX_LENGTH} bytes.
    description: 'Books',
    // Your own ids for the order, to find it again with get_orders, e.g. after a timeout.
    externalApplicationId: '6f1c2b9e-0d4a-4c3e-9b8f-1a2b3c4d5e6f',
    externalOrderId: 'EXAMPLE-' . \date('YmdHis'),
);

// The order travels inside the message, which also carries the request-only coupon code.
$message = new CreateOrderMessage(order: $order);

$sdk = polkurier();

try {
    $quote = null;
    foreach ($sdk->orders()->orderValuation($message) as $valuation) {
        if ($valuation->serviceCode === $order->getCourier()) {
            $quote = $valuation;
        }
    }

    if ($quote === null || !$quote->shipment) {
        echo "{$order->getCourier()} gives no quote for this shipment.\n";
        exit(1);
    }

    \printf("Quote: %.2f PLN net, %.2f PLN gross.\n", $quote->netPrice, $quote->grossPrice);

    if (!hasFlag('--confirm')) {
        echo "\nRe-run with --confirm to place this order.\n";
        exit(0);
    }

    $created = $sdk->orders()->createOrder($message);

    \printf(
        "\nOrder %s placed. Waybills: %s. Gross: %.2f PLN\n",
        $created->orderNumber,
        \implode(', ', $created->label) ?: '(pending)',
        $created->priceGross
    );
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

