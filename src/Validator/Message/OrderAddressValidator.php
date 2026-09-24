<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Request\Order\OrderAddressMessage;
use Polkurier\Webservice\Validator\Rule\NotBlank;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Checks one end of a shipment.
 *
 * Which fields are required depends on the address rather than being fixed, which is why this is a
 * message-level rule and not a value one.
 *
 * The two ends are not held to the same standard, because the API does not hold them to the same
 * standard: an email address is required of the sender and optional for the recipient.
 *
 * A recipient collecting from a pickup point is a further exception. Several carriers skip the
 * street address entirely in that case and need only a telephone number, so an address naming a
 * point is judged on that alone rather than being asked for a street nothing will be delivered to.
 *
 * The formats themselves are not checked here. The telephone number and the postcode declare
 * their own rules on the address, so they hold wherever an address appears; this decides only
 * which of the fields have to be filled in at all.
 */
class OrderAddressValidator
{
    /**
     * @param OrderAddressMessage $address
     * @param ViolationPool $violations
     * @param bool $recipient which end of the shipment this is
     *
     * @return void
     */
    public function collect(OrderAddressMessage $address, ViolationPool $violations, bool $recipient): void
    {
        $notBlank = new NotBlank();

        $violations->check('phone', $address->getPhone(), $notBlank);

        if ($recipient && ($address->getPointId() !== null || $address->getMachineName() !== null)) {
            // Delivery is to the point, so the street address is never used.
            return;
        }

        $violations
            ->check('person', $address->getPerson(), $notBlank)
            ->check('street', $address->getStreet(), $notBlank)
            ->check('city', $address->getCity(), $notBlank)
            ->check('postcode', $address->getPostcode(), $notBlank)
            ->checkWhen(!$recipient, 'email', $address->getEmail(), $notBlank);
    }
}
