<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\KnowledgeBase;

/**
 * Whether a knowledge base index entry is a page or a grouping.
 */
enum KnowledgeBaseEntryTypeEnum: string
{
    /**
     * A page. Its slug can be passed to the article endpoint.
     */
    case Article = 'ARTICLE';

    /**
     * A group of other entries. Fetching it as an article returns nothing.
     */
    case Category = 'CATEGORY';
}
