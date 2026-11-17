<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;

/**
 * A postcode.
 *
 * Polish postcodes must be NN-NNN. Postcodes from other countries are not checked, so a German
 * address is not held to the Polish format.
 *
 * Pass the country code to turn the check off for a field that holds a foreign postcode:
 *
 * ```php
 * #[Postcode]                 // checked against NN-NNN
 * #[Postcode(country: 'DE')]  // not checked
 * private string $postcode;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Postcode implements ValueValidatorInterface
{
    private const string PATTERN = '/^\d{2}-\d{3}$/';

    /**
     * The only country whose postcode format is checked.
     */
    public const string SERVED_COUNTRY = 'PL';

    /**
     * @param string $country ISO code of the country the postcode belongs to
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $country = self::SERVED_COUNTRY,
    ) {}

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    public function validate(mixed $value): array
    {
        // Empty values pass. {@see \Polkurier\Webservice\Validator\Rule\NotBlank} handles required
        // fields. Rejecting them here would make every optional field of this kind mandatory.
        if ($value === null || $value === '') {
            return [];
        }

        if (\strtoupper($this->country) !== self::SERVED_COUNTRY) {
            return [];
        }
        if (!\is_string($value) || \preg_match(self::PATTERN, \trim($value)) !== 1) {
            return ['must be a Polish postcode in the format NN-NNN'];
        }

        return [];
    }
}
