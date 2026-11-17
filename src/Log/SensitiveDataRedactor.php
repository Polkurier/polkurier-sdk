<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Log\Attribute\Sensitive;

/**
 * Hides the fields marked {@see Sensitive} in request and response bodies before they are logged:
 * the API token, passwords, PINs, two-factor codes and tokens, access tokens and a contact form's
 * nonce.
 *
 * A key named `pin` or ending in `_pin` is hidden as well, in any message and whatever class it
 * comes from.
 */
class SensitiveDataRedactor extends FieldRedactor
{
    /**
     * @return class-string
     */
    protected function actsOn(): string
    {
        return Sensitive::class;
    }

    /**
     * A key named `pin` or ending in `_pin` is hidden too, whether or not a class declares it: a
     * PIN reaches the API under no other name, and nothing else is called one.
     *
     * @param string $key
     * @param list<class-string> $marks
     *
     * @return bool
     */
    protected function hides(string $key, array $marks): bool
    {
        return parent::hides($key, $marks) || $key === 'pin' || \str_ends_with($key, '_pin');
    }
}
