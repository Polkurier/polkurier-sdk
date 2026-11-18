<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The page size and page index of a paged list request.
 *
 * An endpoint with a page size limit declares it on its message with
 * {@see \Polkurier\Webservice\Validator\Rule\PageSizeAtMost}.
 *
 * @api
 */
abstract readonly class PagedMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_PAGE_SIZE = 'pagesize';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * @param int|null $pageSize entries per page
     * @param int|null $page 1-based page index
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_PAGE_SIZE)]
        #[OmitWhenEmpty]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        public ?int $pageSize = null,

        #[Serializer\SerializedName(self::FIELD_PAGE)]
        #[OmitWhenEmpty]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        public ?int $page = null,
    ) {}
}
