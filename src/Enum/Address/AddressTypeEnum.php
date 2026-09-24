<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Address;

/**
 * Which role an address-book entry is filed under.
 *
 * An entry stored for both roles is returned once per role. Note that `get_addresses` accepts an
 * empty type to mean "either", which has no representation here — omit the filter instead.
 */
enum AddressTypeEnum: string
{
    case Sender = 'SENDER';
    case Recipient = 'RECIPIENT';
}
