<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\MessageRuleInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Checks a postcode against the country field beside it.
 *
 * The postcode format depends on the country, so the rule reads 2 fields and goes on the class.
 *
 * If there is no country field, put {@see \Polkurier\Webservice\Validator\Rule\Postcode} on the
 * property instead. It checks against Poland unless given another country.
 *
 * ```php
 * #[PostcodeForCountry(postcode: 'postcode', country: 'country')]
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class PostcodeForCountry implements MessageRuleInterface
{
    /**
     * @param string $postcode the property holding the postcode
     * @param string $country the property holding its ISO country code
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

        $violations->check(
            $fields->field($this->postcode),
            $fields->value($this->postcode),
            new Postcode(\is_string($country) && $country !== '' ? $country : Postcode::SERVED_COUNTRY),
        );
    }
}
