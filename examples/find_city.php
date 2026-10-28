<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * find_city — look up the towns a postcode covers.
 *
 * One postcode can serve several localities. Only Polish postcodes are looked up: any other
 * country, like an unknown postcode, gives an empty list.
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

