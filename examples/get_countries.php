<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_countries — list the countries one carrier ships to.
 *
 * Answers with a map of ISO code to country name, not a list of objects. The carrier is required:
 * this is that carrier's coverage, not the service's.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Courier\GetCountriesMessage;


require __DIR__ . '/bootstrap.php';

try {
    $countries = polkurier()->courier()->getCountries(new GetCountriesMessage(couriers: 'UPS_EX'));

    foreach ($countries as $code => $name) {
        \printf("%-4s %s\n", $code, $name);
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

