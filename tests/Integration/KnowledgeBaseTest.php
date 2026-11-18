<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\KnowledgeBase\GetKnowledgeBaseArticleMessage;
use Polkurier\Webservice\Dto\Request\KnowledgeBase\SearchKnowledgeBaseMessage;

#[Group('integration')]
#[CoversNothing]
final class KnowledgeBaseTest extends IntegrationTestCase
{
    public function testTheIndexListsArticlesAndOneIsFetched(): void
    {
        $index = $this->sdk->knowledgeBase()->getIndex();
        self::assertNotEmpty($index);

        $articles = \array_values(\array_filter($index, static fn($entry): bool => $entry->slug !== ''
            && $entry->type?->value === 'ARTICLE'));
        if ($articles === []) {
            self::markTestSkipped('The index lists no article.');
        }

        $this->assertReads(fn() => $this->sdk->knowledgeBase()->getArticle(
            new GetKnowledgeBaseArticleMessage(id: $articles[0]->slug),
        ));
    }

    public function testASearchReads(): void
    {
        $this->assertReads(
            fn() => $this->sdk->knowledgeBase()->search(new SearchKnowledgeBaseMessage(query: 'paczka')),
        );
    }
}
