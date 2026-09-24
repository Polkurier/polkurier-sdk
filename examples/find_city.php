<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * find_city — look up the towns a postcode covers.
 *
 * One postcode can serve several localities, so the answer is a list rather than a single name.
 * Only Poland is served; asking about any other country answers with an empty list rather than an
 * error.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Geo\FindCityMessage;


require __DIR__ . '/bootstrap.php';

try {
    $cities = polkurier()->geo()->findCity(new FindCityMessage(postcode: '61-001'));

    foreach ($cities as $city) {
        echo $city->getName(), "\n";
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

