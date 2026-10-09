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
 * It confirms a PIN in your UI before an operation that needs it.
 */
readonly class ValidatePinMessage extends PinMessage
{
}
