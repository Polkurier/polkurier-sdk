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
 * Two fields that have to be in order.
 *
 * Ranges the API accepts inverted and then satisfies with nothing, which is indistinguishable from
 * there being nothing to find. Works for dates and for numbers.
 *
 * Either end being absent means there is no range to judge, so the rule passes.
 *
 * ```php
 * #[InOrder(earlier: 'dateFrom', later: 'dateTo')]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class InOrder implements MessageRuleInterface
{
    /**
     * @param string $earlier the property that must not be the greater of the two
     * @param string $later
     * @param string|null $because appended to the report in place of the default wording
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $earlier,

        /**
         * @var string
         */
        public readonly string $later,

        /**
         * @var string|null
         */
        public readonly ?string $because = null,
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
        $earlier = $fields->value($this->earlier);
        $later = $fields->value($this->later);

        if ($earlier === null || $later === null) {
            return;
        }
        if (!\is_scalar($earlier) && !($earlier instanceof \DateTimeInterface)) {
            return;
        }
        if ($earlier <= $later) {
            return;
        }

        $violations->add(
            $fields->field($this->earlier),
            $this->because ?? \sprintf('must not be greater than %s', $fields->field($this->later))
        );
    }
}
