<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Validator\Rule\NotBlank;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Checks one end of a shipment.
 *
 * The required fields depend on the address, so this is a message-level rule.
 *
 * As in the API, an email address is required for the sender and optional for the recipient. An
 * order's cover address is a sender address the carrier prints, so it is checked as one.
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
     * @param Address $address
     * @param ViolationPool $violations
     * @param AddressTypeEnum $type the role the address plays in the order
     *
     * @return void
     */
    public function collect(Address $address, ViolationPool $violations, AddressTypeEnum $type): void
    {
        $notBlank = new NotBlank();
        $recipient = $type === AddressTypeEnum::Recipient;

        $violations->check(Address::FIELD_PHONE, $address->getPhone(), $notBlank);

        if ($recipient && $address->getPointId() !== null) {
            // Delivery goes to the point, so the street address is not required.
            return;
        }

        $violations
            ->check(Address::FIELD_PERSON, $address->getPerson(), $notBlank)
            ->check(Address::FIELD_STREET, $address->getStreet(), $notBlank)
            ->check(Address::FIELD_CITY, $address->getCity(), $notBlank)
            ->check(Address::FIELD_POSTCODE, $address->getPostcode(), $notBlank)
            ->checkWhen(!$recipient, Address::FIELD_EMAIL, $address->getEmail(), $notBlank);
    }
}
