<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

/**
 * Sets or changes the account's telephone PIN.
 *
 * This is the short code you give when contacting support. It also unlocks PIN-protected
 * operations. The allowed length is reported by `get_system_info` under `security`.
 */
readonly class ChangePinMessage extends PinMessage
{
}
