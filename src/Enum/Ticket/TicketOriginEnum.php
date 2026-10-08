<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Ticket;

/**
 * Where a support ticket or message came from.
 *
 * Defaults to {@see self::Api}. A mobile app sends its platform, so the support team can see how
 * the customer contacted them.
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
