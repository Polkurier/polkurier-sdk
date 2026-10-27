<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Request\Common\DeleteMessage;
use Polkurier\Webservice\Request\PackTemplate\SaveMessage;

#[Group('integration')]
#[CoversNothing]
final class PackTemplatesTest extends IntegrationTestCase
{
    public function testATemplateIsSaved(): string
    {
        self::requireSandbox();

        $template = $this->sdk->packTemplates()->savePackTemplates(new SaveMessage(
            name: 'SDK box ' . \bin2hex(\random_bytes(3)),
            length: 30,
            width: 20,
            height: 15,
            weight: 2.5,
        ));

        self::assertNotNull($template->getId());

        return (string)$template->getId();
    }

    #[Depends('testATemplateIsSaved')]
    public function testTheTemplateIsListedAndDeleted(string $id): void
    {
        $ids = \array_map(static fn($template) => $template->getId(), $this->sdk->packTemplates()->getPackTemplates());
        self::assertContains($id, $ids);

        self::assertTrue($this->sdk->packTemplates()->deletePackTemplates(new DeleteMessage(id: $id)));
    }
}
