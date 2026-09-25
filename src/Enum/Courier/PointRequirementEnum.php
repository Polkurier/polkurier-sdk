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
 * Returned per carrier by `available_carriers`. REQUIRED means an order is rejected if the
 * address has no point id.
 */
enum PointRequirementEnum: string
{
    /**
     * The carrier doesn't use pickup points here. Don't send one.
     */
    case None = 'NONE';

    /**
     * A point can be chosen, but a street address works too.
     */
    case Optional = 'OPTIONAL';

    /**
     * A point must be chosen or the order is rejected.
     */
    case Required = 'REQUIRED';
}
