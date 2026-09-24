<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_pack_templates — list the saved parcel templates.
 *
 * A convenience for repeat consignments. Nothing binds an order to a template: read one and copy
 * its values into the payload.
 */
declare(strict_types=1);

use Polkurier\Webservice\Exception\Exception;


require __DIR__ . '/bootstrap.php';

try {
    foreach (polkurier()->packTemplates()->getPackTemplates() as $template) {
        \printf(
            "%-8s %-20s %3dx%3dx%3d cm  %5.2f kg  %s\n",
            $template->getId() ?? '',
            $template->getName(),
            $template->getLength(),
            $template->getWidth(),
            $template->getHeight(),
            $template->getWeight(),
            $template->isDefault() ? '(default)' : ''
        );
    }
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

