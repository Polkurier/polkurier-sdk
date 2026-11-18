<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\KnowledgeBase;

use Polkurier\Webservice\Dto\Request\Common\IdMessage;

/**
 * Identifies the article to fetch by its slug.
 *
 * @api
 */
final readonly class GetKnowledgeBaseArticleMessage extends IdMessage
{
}
