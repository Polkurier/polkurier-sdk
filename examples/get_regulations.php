<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * get_regulations — read the current terms or privacy policy.
 *
 * Carries the text and the id that accept_regulations expects. get_account_info reports whether
 * the account has accepted the current version, which is what tells you to show this.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Account\GetRegulationsMessage;


require __DIR__ . '/bootstrap.php';

try {
    $regulations = polkurier()->account()->getRegulations(
        new GetRegulationsMessage(type: RegulationsTypeEnum::Regulations)
    );

    \printf("%s (%s)\n\n", $regulations->getTitle(), $regulations->getId());
    echo \substr(\strip_tags($regulations->getContent()), 0, 400), "…\n";
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

