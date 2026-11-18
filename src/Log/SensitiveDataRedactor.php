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
}
