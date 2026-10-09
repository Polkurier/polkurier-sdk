<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * What an order line item charges for.
 *
 * Items are only returned if the listing request asks for them.
 */
enum OrderItemTypeEnum: string
{
    /**
     * An additional service, such as insurance or a notification.
     */
    case Service = 'SERVICE';

    /**
     * Carriage of one of the parcels.
     */
    case Pack = 'PACK';
}
