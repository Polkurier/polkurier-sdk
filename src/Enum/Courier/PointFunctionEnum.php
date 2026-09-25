<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * What a pickup point can be used for, as a filter on `get_courier_point`.
 *
 * If you pass several, only points that support all of them are returned.
 */
enum PointFunctionEnum: string
{
    /**
     * Accepts cash-on-delivery payments.
     */
    case Cod = 'cod';

    /**
     * Parcels can be dropped off here.
     */
    case Send = 'send';

    /**
     * Parcels can be collected here.
     */
    case Collect = 'collect';
}
