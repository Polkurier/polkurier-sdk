<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFieldReader;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * The largest page size a paged request may ask for.
 *
 * Declared on a {@see \Polkurier\Webservice\Dto\Request\Common\PagedMessage} whose endpoint has a
 * limit. An unset page size passes.
 *
 * The rule reports the maximum alone. The page size's lower bound and its type are declared on
 * {@see \Polkurier\Webservice\Dto\Request\Common\PagedMessage::$pageSize}, which reports them
 * itself.
 *
 * ```php
 * #[PageSizeAtMost(self::MAX_PAGE_SIZE)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PageSizeAtMost implements MessageRuleInterface
{
    /**
     * The property this rule reads. {@see self::listProperties()} names it, so a class carrying
     * the rule without providing it is rejected as its rules are read, by
     * {@see \Polkurier\Webservice\Validator\AttributeValidator}.
     */
    private const string PROPERTY = 'pageSize';

    /**
     * @param int $max
     */
    public function __construct(
        public readonly int $max,
    ) {}

    /**
     * @param MessageFieldReader $fields
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(MessageFieldReader $fields, ViolationPool $violations): void
    {
        $pageSize = $fields->readValue(self::PROPERTY);
        if (\is_int($pageSize) && $pageSize > $this->max) {
            $violations->add(
                $fields->readField(self::PROPERTY),
                \sprintf('must be at most %d', $this->max),
                ValidationErrorCodeEnum::OutOfRange,
                ['maximum' => $this->max],
            );
        }
    }

    /**
     * @return list<string>
     */
    public function listProperties(): array
    {
        return [self::PROPERTY];
    }
}
