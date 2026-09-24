<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * validate_coupon_code — check a discount code before applying it.
 *
 * A code can be valid in general and still not apply to a particular order, which is why the
 * answer carries two flags. Pass the order's carrier, shipment type and parcels to have the second
 * one mean something.
 */
declare(strict_types=1);

use Polkurier\Webservice\Enum\Order\ShipmentTypeEnum;
use Polkurier\Webservice\Exception\Exception;
use Polkurier\Webservice\Request\Order\PackMessage;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;


require __DIR__ . '/bootstrap.php';

$code = arguments()[0] ?? 'PROMO2026';

try {
    $coupon = polkurier()->system()->validateCouponCode(new ValidateCouponMessage(
        couponCode: $code,
        courier: 'UPS',
        shipmentType: ShipmentTypeEnum::Box,
        packs: [new PackMessage(length: 30, width: 20, height: 15, weight: 2.5)],
    ));

    \printf("%s: %s", $coupon->getCode(), $coupon->isValid() ? 'valid' : 'not valid');
    \printf(", %s for this order\n", $coupon->isValidForOrder() ? 'usable' : 'not usable');
    \printf("  %s\n", $coupon->getDescription());
} catch (Exception $e) {
    \fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

