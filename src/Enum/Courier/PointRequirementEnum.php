<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * Whether a carrier needs a pickup point chosen for one end of a shipment.
 *
 * Reported per carrier by `available_carriers`. REQUIRED means an order without a point id on the
 * relevant address will be rejected.
 */
enum PointRequirementEnum: string
{
    /**
     * The carrier does not use pickup points here; sending one has no effect.
     */
    case None = 'NONE';

    /**
     * A point may be chosen, but a street address is equally valid.
     */
    case Optional = 'OPTIONAL';

    /**
     * A point must be chosen or the order is rejected.
     */
    case Required = 'REQUIRED';
}
