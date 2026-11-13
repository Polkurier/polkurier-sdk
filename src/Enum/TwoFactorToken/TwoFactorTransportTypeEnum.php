<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\TwoFactorToken;

/**
 * How a two-factor code reaches the customer.
 *
 * @api
 */
enum TwoFactorTransportTypeEnum: string
{
    /**
     * The code is sent by text message.
     */
    case Sms = 'SMS';

    /**
     * The code is sent to an email address.
     */
    case Email = 'EMAIL';

    /**
     * The customer reads the code from an authenticator app.
     */
    case Authenticator = 'AUTHENTICATOR';
}
