<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\MessageRuleInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * The largest page size a paged request may ask for.
 *
 * Declared on a {@see \Polkurier\Webservice\Request\Common\PagedMessage} whose endpoint has a
 * limit. An unset page size passes.
 *
 * ```php
 * #[PageSizeAtMost(self::MAX_PAGE_SIZE)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PageSizeAtMost implements MessageRuleInterface
{
    /**
     * @param int $max
     */
    public function __construct(
        /**
         * @var int
         */
        public readonly int $max,
    ) {}

    /**
     * @param MessageFields $fields
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(MessageFields $fields, ViolationPool $violations): void
    {
        // The lower bound and the type are checked on the property itself.
        $pageSize = $fields->value('pageSize');
        if (\is_int($pageSize) && $pageSize > $this->max) {
            $violations->add($fields->field('pageSize'), \sprintf('must be between 1 and %d', $this->max));
        }
    }
}
