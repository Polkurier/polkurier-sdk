<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

/**
 * Checks a security PIN without changing anything.
 *
 * Use it to confirm a PIN before a PIN-locked operation. Not the telephone PIN.
 */
readonly class ValidatePinMessage extends PinMessage
{
}
