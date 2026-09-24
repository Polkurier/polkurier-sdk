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
 * Checks a postcode against the country field beside it.
 *
 * What a postcode may look like depends on where it is, so the rule needs two fields and belongs
 * where both are visible. A property knows its own value and nothing else; making one name a
 * sibling would tie two fields together from a place that can see only one of them, and leave the
 * pairing to be kept correct by hand.
 *
 * Where there is no country field to read, {@see Postcode} goes on the property on its own and
 * checks against the country the API serves by default.
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
