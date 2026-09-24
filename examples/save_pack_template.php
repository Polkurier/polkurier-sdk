<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * save_pack_template — add a parcel template, or update one.
 *
 * Passing an id updates that template; leaving it out creates a new one. Naming a carrier ties the
 * template to that carrier's services, which is what makes the service list meaningful.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example writes a real template. Re-run with --confirm to do it for real.\n";
    exit(0);
}

try {
    $template = polkurier()->packTemplates()->savePackTemplates(new SaveMessage(
        name: 'Karton A',
        length: 30,
        width: 20,
        height: 15,
        weight: 2.5,
        shipmentType: ShipmentTypeEnum::Box,
        description: 'Standardowy karton wysyłkowy',
    ));

    \printf("Saved as %s\n", $template->getId() ?? '(no id returned)');
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

