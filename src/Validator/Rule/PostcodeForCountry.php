<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Checks a postcode against the country field beside it.
 *
 * The postcode format depends on the country, so the rule reads 2 fields and goes on the class.
 *
 * An empty country field is checked against `whenEmpty`, or not checked at all when that is null.
 * Which one a message wants depends on what an empty country means in that payload: a field the
 * API reads as a country of its own, or a field the message leaves out.
 *
 * If there is no country field, put {@see \Polkurier\Webservice\Validator\Rule\Postcode} on the
 * property instead. It checks against Poland unless given another country.
 *
 * ```php
 * #[PostcodeForCountry(postcode: 'postcode', country: 'country', whenEmpty: self::DEFAULT_COUNTRY)]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class PostcodeForCountry implements MessageRuleInterface
{
    /**
     * @param string $postcode the property holding the postcode
     * @param string $country the property holding its ISO country code
     * @param string|null $whenEmpty the country assumed when that property is empty, or null to
     *        check nothing
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $postcode,

        /**
         * @var string
         */
        public readonly string $country,

        /**
         * @var string|null
         */
        public readonly ?string $whenEmpty = null,
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
        $country = $fields->value($this->country);

        if (!\is_string($country) || $country === '') {
            $country = $this->whenEmpty;
        }
        if ($country === null) {
            return;
        }

        $violations->check(
            $fields->field($this->postcode),
            $fields->value($this->postcode),
            new Postcode($country),
        );
    }
}
