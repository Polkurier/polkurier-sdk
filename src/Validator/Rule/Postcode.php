<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Attribute;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;

/**
 * A postcode, in the only format the API parses.
 *
 * Polish postcodes are NN-NNN. No other country's format is read by the API, so a postcode belonging
 * to one is passed over rather than judged — imposing the Polish shape on a German address would
 * reject something the service accepts perfectly well.
 *
 * Where the country is a sibling field, name it and the rule reads it from there:
 *
 * ```php
 * #[Postcode]                 // the country the API serves
 * #[Postcode(country: 'DE')]   // anywhere else
 * private string $postcode;
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Postcode implements ValueValidatorInterface
{
    private const string PATTERN = '/^\d{2}-\d{3}$/';

    /**
     * The only country the API serves domestic postcodes for.
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
        // Absence is not this rule's business; that is what NotBlank is for. Saying so here would
        // make every optional field of this kind mandatory.
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
