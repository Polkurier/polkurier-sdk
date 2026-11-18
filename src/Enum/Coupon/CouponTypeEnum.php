<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Coupon;

/**
 * What a coupon takes off the price.
 *
 * `validate_coupon_code` returns it under `type`, and sends it as null when the code matches no
 * coupon.
 *
 * @api
 */
enum CouponTypeEnum: string
{
    /**
     * An amount off, in PLN.
     */
    case Value = 'VALUE';

    /**
     * A share off, as a percentage.
     */
    case Percent = 'PERCENT';

    /**
     * A fixed price, replacing the one quoted.
     */
    case Static = 'STATIC';
}
