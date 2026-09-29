<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Webservice;

use Polkurier\Webservice\Api\ConfigInterface;
use Polkurier\Webservice\Api\ConfigProviderInterface;

/**
 * Returns whatever configuration the test last put in $current.
 */
final class SwitchingConfigProvider implements ConfigProviderInterface
{
    public function __construct(public ConfigInterface $current) {}

    public function getConfig(): ConfigInterface
    {
        return $this->current;
    }
}
