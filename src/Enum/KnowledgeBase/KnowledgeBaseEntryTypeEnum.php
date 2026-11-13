<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\KnowledgeBase;

/**
 * Whether a knowledge base index entry is a page or a grouping.
 *
 * @api
 */
enum KnowledgeBaseEntryTypeEnum: string
{
    /**
     * A page. Its slug can be passed to the article endpoint.
     */
    case Article = 'ARTICLE';

    /**
     * A group of other entries. The article endpoint answers its slug with NOT_FOUND.
     */
    case Category = 'CATEGORY';
}
