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
 * An international bank account number, checked properly.
 *
 * Verifies the ISO 13616 check digits rather than just counting characters, so a transposed pair
 * is caught here instead of failing somewhere downstream — which matters more than usual, because
 * the field decides where collected money is sent and a wrong-but-plausible number is a payment
 * to a stranger.
 *
 * Two forms are accepted. A full IBAN carries its own country code. A bare national number does
 * not, so the configured country is prefixed before checking — which is the common case here,
 * since Polish accounts are normally written as the 26-digit national number with the "PL" left
 * off. Nothing is recalculated in the process: a national number already carries its check digits,
 * and Poland's leading pair is exactly the IBAN pair.
 *
 * Formatting is ignored entirely: spaces and grouping are stripped first, as the API does.
 *
 * A country outside the registry is passed over rather than rejected. The list below is current as
 * of writing and countries do join it, and refusing an account the bank itself accepts would be
 * the worse failure.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Iban implements ValueValidatorInterface
{
    /**
     * IBAN length per country, from the ISO 13616 registry.
     */
    private const array LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16,
        'BG' => 22, 'BH' => 22, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28,
        'CZ' => 24, 'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24,
        'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18,
        'GR' => 27, 'GT' => 28, 'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IQ' => 23,
        'IS' => 26, 'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LC' => 32,
        'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21, 'LY' => 25, 'MC' => 27, 'MD' => 24,
        'ME' => 22, 'MK' => 19, 'MR' => 27, 'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15,
        'PK' => 24, 'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22,
        'SA' => 24, 'SC' => 31, 'SD' => 18, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27,
        'ST' => 25, 'SV' => 28, 'TL' => 23, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22,
        'VG' => 24, 'XK' => 20,
    ];

    /**
     * @param string $country ISO country code assumed when the value carries no prefix of its own
     */
    public function __construct(
        /**
         * @var string
         */
        public readonly string $country = 'PL',
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

        if (!\is_string($value)) {
            return ['must be a bank account number'];
        }

        $normalised = \strtoupper((string)\preg_replace('/[\s-]/', '', $value));

        if ($normalised === '') {
            return ['must not be empty'];
        }
        if (\preg_match('/^[A-Z0-9]+$/', $normalised) !== 1) {
            return ['must contain only letters and digits'];
        }

        // A national number already carries its check digits — Poland's leading pair is exactly
        // the IBAN pair — so the country code is prefixed and nothing is recalculated.
        $iban = \preg_match('/^[A-Z]{2}\d{2}/', $normalised) === 1
            ? $normalised
            : \strtoupper($this->country) . $normalised;

        $country = \substr($iban, 0, 2);
        $expected = self::LENGTHS[$country] ?? null;

        if ($expected !== null && \strlen($iban) !== $expected) {
            return [\sprintf('must be %d characters long for %s', $expected, $country)];
        }
        if (!static::checksumHolds($iban)) {
            return ['is not a valid account number; check the digits'];
        }

        return [];
    }

    /**
     * @param string $iban
     *
     * @return bool
     */
    protected static function checksumHolds(string $iban): bool
    {
        return static::mod97(static::toDigits(\substr($iban, 4) . \substr($iban, 0, 4))) === 1;
    }

    /**
     * Letters become their position in the alphabet plus nine, as ISO 13616 requires.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function toDigits(string $value): string
    {
        $digits = '';

        foreach (\str_split($value) as $character) {
            $digits .= \ctype_alpha($character)
                ? (string)(\ord($character) - \ord('A') + 10)
                : $character;
        }

        return $digits;
    }

    /**
     * Modulo 97 over a number too long for an integer, taken in chunks.
     *
     * @param string $digits
     *
     * @return int
     */
    protected static function mod97(string $digits): int
    {
        $remainder = 0;

        foreach (\str_split($digits, 7) as $chunk) {
            $remainder = (int)((string)$remainder . $chunk) % 97;
        }

        return $remainder;
    }
}
