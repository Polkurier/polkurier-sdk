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
use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A field that is required whenever another field is set.
 *
 * For 2 fields that only work as a pair, e.g. an id that means nothing without the application it
 * belongs to. Goes on the class, since it involves 2 fields. Repeat it the other way round when
 * each needs the other.
 *
 * ```php
 * #[RequiredWith(field: 'externalApplicationId', with: 'externalOrderId')]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class RequiredWith implements MessageRuleInterface
{
    /**
     * @param string $field the property that becomes mandatory
     * @param string $with the property whose being set makes $field mandatory
     * @param string|null $reason the report's wording, in place of the default
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $field,

        /**
         * @var string
         */
        public readonly string $with,

        /**
         * @var string|null
         */
        public readonly ?string $reason = null,
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
        $notBlank = new NotBlank();
        if ($notBlank->validate($fields->value($this->with)) !== []) {
            return;
        }

        if ($notBlank->validate($fields->value($this->field)) === []) {
            return;
        }

        $with = $fields->field($this->with);

        $violations->add(
            $fields->field($this->field),
            $this->reason ?? \sprintf('is required when %s is set', $with),
            ValidationErrorCodeEnum::Blank,
            ['field' => $with, 'condition' => 'set'],
        );
    }
}
