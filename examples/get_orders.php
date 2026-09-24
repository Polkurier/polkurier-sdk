<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_orders — page through the order list.
 *
 * The response is always a paging envelope, even when filtering to a single order number. Page
 * size is capped at 100 and defaults to 100, so a complete listing means walking the pages.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\GetOrdersMessage;

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

        foreach ($list->getResult() as $order) {
            \printf(
                "%-16s %-12s %-28s %8.2f PLN\n",
                $order->getNumber(),
                $order->getStatusCode(),
                $order->getCourierName() ?? $order->getCourier(),
                $order->getPriceGross()
            );
        }

        \printf("-- page %d of %d (%d orders total)\n", $list->getCurrentPage(), $list->getTotalPages(), $list->getTotalRows());
        $page++;
    } while ($page <= $list->getTotalPages());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
