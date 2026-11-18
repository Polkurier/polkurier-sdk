<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Coupon;

/**
 * How often a coupon code may be used.
 *
 * `validate_coupon_code` returns it under `usage_type`, and leaves it out when the code matches no
 * coupon.
 *
 * @api
 */
enum CouponUsageTypeEnum: string
{
    /**
     * Usable once in total, by whoever redeems it first.
     */
    case Single = 'SINGLE';

    /**
     * Usable once per customer.
     */
    case Multi = 'MULTI';
}
