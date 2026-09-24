<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * What an order line item is charging for.
 *
 * Items are only returned when a listing asks for them.
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
