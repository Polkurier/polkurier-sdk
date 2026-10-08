<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * accept_regulations — record acceptance of the current terms.
 *
 * The id comes from get_regulations and is version-specific, so acceptance is tied to the text
 * that was shown rather than to the document in general.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\AcceptRegulationsMessage;
use Polkurier\Webservice\Request\Account\GetRegulationsMessage;


require __DIR__ . '/bootstrap.php';

if (!hasFlag('--confirm')) {
    echo "This example records a real acceptance. Re-run with --confirm to do it for real.\n";
    exit(0);
}

$sdk = polkurier();

try {
    $regulations = $sdk->account()->getRegulations(
        new GetRegulationsMessage(type: RegulationsTypeEnum::Regulations)
    );

    $accepted = $sdk->account()->acceptRegulations(
        new AcceptRegulationsMessage(regulationsId: $regulations->getId())
    );

    echo $accepted ? "Accepted {$regulations->getId()}.\n" : "Acceptance was not recorded.\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

