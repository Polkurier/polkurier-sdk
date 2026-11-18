<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\Rule\NotBlank;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Order rules that can't be declared on the fields.
 *
 * Most of an order is checked by attributes on its messages: the carrier must be set, parcel
 * values positive, postcodes in the right format. This class handles the addresses, whose
 * required fields depend on the role each one plays, which the address itself doesn't know, see
 * {@see self::collectAddress()}.
 *
 * Cash-on-delivery rules are declared on
 * {@see \Polkurier\Webservice\Entity\Order\Cod}, so they apply wherever that block is
 * sent.
 *
 * @extends AbstractMessageValidator<Order>
 */
class OrderValidator extends AbstractMessageValidator
{
    /**
     * @param Order $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        // A missing address is reported by its NotBlank rule
        $this->collectAddress($data->getSender(), Order::FIELD_SENDER, AddressTypeEnum::Sender, $violations);
        $this->collectAddress(
            $data->getRecipient(),
            Order::FIELD_RECIPIENT,
            AddressTypeEnum::Recipient,
            $violations,
        );
        // The API reads the cover address as a second sender address and prints it, so it needs
        // what a sender needs. A block with anything in it is charged for, see
        // {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer}.
        $this->collectAddress(
            $data->getCoverAddress(),
            Order::FIELD_COVER_ADDRESS,
            AddressTypeEnum::Sender,
            $violations,
        );
    }

    /**
     * Checks one end of a shipment, under the field that carries it.
     *
     * As in the API, an email address is required for the sender and optional for the recipient.
     *
     * A recipient collecting from a pickup point is another exception. Several carriers need only
     * a phone number in that case, so an address that names a point is only checked for the phone.
     *
     * Formats are not checked here. The phone number and the postcode declare their own rules on
     * the address. This decides which fields must be filled in.
     *
     * @param Address|null $address
     * @param string $field the wire field the address is sent under
     * @param AddressTypeEnum $type the role the address plays in the order
     * @param ViolationPool $violations
     *
     * @return void
     */
    private function collectAddress(
        ?Address $address,
        string $field,
        AddressTypeEnum $type,
        ViolationPool $violations,
    ): void {
        if ($address === null) {
            return;
        }

        $notBlank = new NotBlank();
        $recipient = $type === AddressTypeEnum::Recipient;
        $pool = new ViolationPool();

        $pool->check(Address::FIELD_PHONE, $address->getPhone(), $notBlank);

        // A recipient's parcel that names a point is delivered to the point, so the address needs
        // no street.
        if (!$recipient || $address->getPointId() === null) {
            $pool
                ->check(Address::FIELD_PERSON, $address->getPerson(), $notBlank)
                ->check(Address::FIELD_STREET, $address->getStreet(), $notBlank)
                ->check(Address::FIELD_CITY, $address->getCity(), $notBlank)
                ->check(Address::FIELD_POSTCODE, $address->getPostcode(), $notBlank)
                ->checkWhen(!$recipient, Address::FIELD_EMAIL, $address->getEmail(), $notBlank);
        }

        $violations->mergeUnder($field, $pool);
    }
}
