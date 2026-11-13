<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

/**
 * Sets or changes the account's telephone PIN.
 *
 * The code given when contacting support: 4 digits, no run of 3 consecutive (123, 321) or identical
 * (111) digits. Not the security PIN.
 */
final readonly class ChangePinMessage extends PinMessage
{
}
