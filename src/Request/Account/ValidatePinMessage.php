<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

/**
 * Checks a security PIN without changing anything.
 *
 * Use it to confirm a PIN in your UI before an operation that needs it.
 */
readonly class ValidatePinMessage extends PinMessage
{
}
