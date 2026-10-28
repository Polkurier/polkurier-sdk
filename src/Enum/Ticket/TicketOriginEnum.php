<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * The channel a support ticket was raised from.
 *
 * Defaults to {@see self::Api}.
 */
enum TicketOriginEnum: string
{
    /**
     * Raised through the API.
     */
    case Api = 'API';

    /**
     * Raised from the Android app.
     */
    case MobileAndroid = 'MOBILE_ANDROID';

    /**
     * Raised from the iOS app.
     */
    case MobileIos = 'MOBILE_IOS';
}
