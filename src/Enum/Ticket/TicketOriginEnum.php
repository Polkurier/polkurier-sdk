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
 * Only the channels an API client can legitimately claim are listed. It defaults to
 * {@see self::Api}, and a mobile app should identify itself with the matching platform so the
 * support team can see how the customer reached them.
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
