<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Address;

/**
 * The role an address book entry is saved under.
 *
 * An entry saved for both roles is returned once per role. `get_addresses` also accepts an empty
 * type meaning "either". There is no case for that here, so leave the filter out instead.
 *
 * @api
 */
enum AddressTypeEnum: string
{
    case Sender = 'SENDER';
    case Recipient = 'RECIPIENT';
}
