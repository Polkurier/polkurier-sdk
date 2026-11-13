<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Geo\FindCityMessage;

#[Group('integration')]
#[CoversNothing]
final class GeoTest extends IntegrationTestCase
{
    public function testAPostcodeFindsItsLocality(): void
    {
        self::assertNotEmpty($this->sdk->geo()->findCity(new FindCityMessage(postcode: '61-001')));
    }

    public function testAMapTokenIsIssued(): void
    {
        $this->assertReads(fn() => $this->sdk->geo()->getMapToken());
    }
}
