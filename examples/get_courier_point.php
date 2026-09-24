<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_courier_point — search pickup points, or fetch one by its id.
 *
 * One endpoint with two shapes, so the SDK gives it two methods: findCourierPoints() searches and
 * answers with a list, getCourierPoint() takes an id and answers with one point or null. The
 * carrier list is required either way — an id is looked up within the carriers named, and an empty
 * list is rejected.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Courier\GetPointMessage;


require __DIR__ . '/bootstrap.php';

$sdk = polkurier();
$couriers = ['INPOST_PACZKOMAT'];

try {
    $points = $sdk->courier()->findCourierPoints(new GetPointMessage(
        couriers: $couriers,
        searchQuery: 'Poznań',
        functions: [PointFunctionEnum::Send],
        limit: 5,
    ));

    foreach ($points as $point) {
        \printf("%-10s %-30s %s\n", $point->getId(), $point->getName(), $point->getAddress());
    }

    if ($points === []) {
        echo "No points matched.\n";
        exit(0);
    }

    // The same endpoint, asked for one point. An id that matches nothing answers with null
    // rather than an error, so the null check is the "not found" branch.
    $one = $sdk->courier()->getCourierPoint($points[0]->getId(), $couriers);

    if ($one === null) {
        echo "That id is no longer listed.\n";
        exit(0);
    }

    \printf("\n%s is %s\n", $one->getId(), $one->isAvailable() ? 'open' : 'closed');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

