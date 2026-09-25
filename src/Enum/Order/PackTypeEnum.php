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
 * Carriers differ in which types they accept and how they price them. `available_carriers` lists
 * the supported types per carrier. Use a value from this enum.
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
     * A long item, priced by length instead of volume.
     */
    case Long = 'DLU';
}
