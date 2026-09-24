<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A coupon is checked either on its own or against an order, and an order means all three fields.
 *
 * The carrier, the shipment type and the parcels describe the order the coupon is being tried
 * against. Send none of them and the API answers whether the coupon is valid at all; send all
 * three and it also answers whether it applies to that order. Send some of them and it rejects the
 * call, naming whichever it noticed missing first.
 *
 * Which is why this reports all of them at once instead: being told the carrier is missing, fixing
 * that, and then being told the shipment type is missing is three round trips to learn one thing.
 *
 * @extends AbstractMessageValidator<ValidateCouponMessage>
 */
class ValidateCouponMessageValidator extends AbstractMessageValidator
{
    /**
     * @param MessageInterface $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        /** @var ValidateCouponMessage $data */
        $supplied = [
            ValidateCouponMessage::FIELD_COURIER => $data->getCourier() !== null,
            ValidateCouponMessage::FIELD_SHIPMENT_TYPE => $data->getShipmentType() !== null,
            ValidateCouponMessage::FIELD_PACKS => $data->getPacks() !== [],
        ];

        if (!\in_array(true, $supplied, true) || !\in_array(false, $supplied, true)) {
            return;
        }

        foreach ($supplied as $field => $given) {
            if (!$given) {
                $violations->add($field, 'is required when checking a coupon against an order');
            }
        }
    }
}
