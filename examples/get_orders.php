<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_orders — page through the order list.
 *
 * A page holds at most 100 orders, and 100 by default, so a complete listing means walking the
 * pages.
 */
declare(strict_types=1);

use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Exception\Exception;

require __DIR__ . '/bootstrap.php';

$sdk = polkurier();
$page = 1;

try {
    do {
        $list = $sdk->orders()
            ->getOrders(new GetOrdersMessage(
                dateFrom: new DateTimeImmutable('-30 days'),
                pageSize: 100,
                page: $page,
                packs: true,
            ));

        foreach ($list->result as $order) {
            \printf(
                "%-16s %-12s %-28s %8.2f PLN\n",
                $order->getNumber(),
                $order->getStatusCode(),
                $order->getCourierName() ?? $order->getCourier(),
                $order->getPriceGross()
            );
        }

        \printf(
            "-- page %d of %d (%d orders total)\n",
            $list->currentPage,
            $list->totalPages,
            $list->totalRows,
        );
        $page++;
    } while ($page <= $list->totalPages);
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
