<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A coupon is checked on its own or against an order. Checking against an order needs all 3
 * order fields.
 *
 * The carrier, the shipment type and the parcels describe the order. With none of them, the API
 * says whether the coupon is valid at all. With all 3, it also says whether the coupon applies to
 * that order. With only some, the call is rejected.
 *
 * This validator reports all missing fields at once.
 *
 * @extends AbstractMessageValidator<ValidateCouponMessage>
 */
class ValidateCouponMessageValidator extends AbstractMessageValidator
{
    /**
     * @param ValidateCouponMessage $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        $supplied = [
            ValidateCouponMessage::FIELD_COURIER => $data->courier !== null,
            ValidateCouponMessage::FIELD_SHIPMENT_TYPE => $data->shipmentType !== null,
            ValidateCouponMessage::FIELD_PACKS => $data->packs !== [],
        ];

        if (!\in_array(true, $supplied, true) || !\in_array(false, $supplied, true)) {
            return;
        }

        foreach ($supplied as $field => $given) {
            if (!$given) {
                // The 3 are required together, so no single field is the one that made this one
                // required: `condition` says a field of the group was given and `field` is left
                // out, because naming an arbitrary one of the other 2 would say otherwise.
                $violations->add(
                    $field,
                    'is required when checking a coupon against an order',
                    ValidationErrorCodeEnum::Blank,
                    ['condition' => 'any_set'],
                );
            }
        }
    }
}
