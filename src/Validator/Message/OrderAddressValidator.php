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
 * The required fields depend on the address, so this is a message-level rule.
 *
 * As in the API, an email address is required for the sender and optional for the recipient.
 *
 * A recipient collecting from a pickup point is another exception. Several carriers need only a
 * telephone number in that case, so an address that names a point is only checked for the phone.
 *
 * Formats are not checked here. The telephone number and the postcode declare their own rules on
 * the address. This class only decides which fields must be filled in.
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
            // Delivery goes to the point, so the street address is not required.
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
