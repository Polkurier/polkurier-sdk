<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * The type of an individual parcel within an order.
 *
 * Carriers differ in which of these they accept and how they price them; `available_carriers`
 * reports the supported set per carrier. Use the enum rather than a string: a value outside it is
 * not reported as an error, and is not honoured as sent.
 */
enum PackTypeEnum: string
{
    /**
     * A regular parcel within the carrier's ordinary size and weight limits.
     */
    case Standard = 'ST';

    /**
     * Oversized or awkwardly shaped, outside the carrier's standard limits.
     * Usually carries a surcharge.
     */
    case NonStandard = 'NST';

    /**
     * A half pallet.
     */
    case HalfPalette = 'PPAL';

    /**
     * A full pallet.
     */
    case Palette = 'PAL';

    /**
     * A long item, priced on length rather than volume.
     */
    case Long = 'DLU';
}
