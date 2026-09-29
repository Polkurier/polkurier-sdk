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
     * A quarter pallet, 60 × 40 cm.
     */
    case QuarterPalette = 'VP';

    /**
     * A half pallet, 60 × 80 cm.
     */
    case HalfPalette = 'PPAL';

    /**
     * A pallet, 120 × 120 cm.
     */
    case Palette = 'PAL';

    /**
     * A pallet, 120 × 100 cm.
     */
    case Palette120x100 = 'EUR2';

    /**
     * A pallet, 150 × 150 cm.
     */
    case Palette150x150 = 'PA15';

    /**
     * A long item, priced by length instead of volume.
     */
    case Long = 'DLU';
}
