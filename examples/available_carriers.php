<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * available_carriers — list the carriers this account may use.
 *
 * Start here when building an integration: the carrier codes this returns are what `create_order`
 * and `order_valuation_v2` expect in their `courier` field, and the additional data describes which
 * services, pack types and pickup rules each carrier supports.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Courier\AvailableCarriersMessage;
use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    $carriers = polkurier()->courier()
        ->getAvailableCarriers(new AvailableCarriersMessage(additionalData: true));

    foreach ($carriers as $carrier) {
        \printf(
            "%-24s %-40s %s\n",
            $carrier->serviceCode,
            $carrier->name,
            $carrier->foreignShipments ? 'international' : 'domestic'
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

