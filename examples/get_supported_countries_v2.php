<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * get_supported_countries_v2 — list every country the service ships to, with its provinces.
 *
 * The richer of the two country listings: each entry carries its provinces, whether it is in the
 * EU, and whether it is the default country (Poland). Use the provinces to populate an address
 * form for countries that have them.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->courier()->getSupportedCountries() as $country) {
        \printf(
            "%-4s %-28s %s  (%d provinces)\n",
            $country->getId(),
            $country->getName(),
            $country->isUe() ? 'EU' : '  ',
            \count($country->getProvinces())
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

