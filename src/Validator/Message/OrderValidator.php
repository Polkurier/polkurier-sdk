<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * Order rules that can't be declared on the fields.
 *
 * Most of an order is checked by attributes on its messages: the carrier must be set, parcel
 * values positive, postcodes in the right format. This class handles the addresses, where the
 * required fields depend on context.
 *
 * An email address is required for the sender and optional for the recipient. A recipient who
 * collects from a pickup point needs no street address. The address message doesn't know which
 * role it plays, so the check happens here.
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
     * @param OrderAddressValidator $addressValidator
     */
    public function __construct(
        /**
         * @var OrderAddressValidator
         */
        protected readonly OrderAddressValidator $addressValidator = new OrderAddressValidator(),
    ) {}

    /**
     * @param MessageInterface $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        /** @var Order $data */
        // A missing address is reported by its NotBlank rule
        $sender = $data->getSender();
        if ($sender !== null) {
            $pool = new ViolationPool();
            $this->addressValidator->collect($sender, $pool, AddressTypeEnum::Sender);
            $violations->mergeUnder(Order::FIELD_SENDER, $pool);
        }

        $recipient = $data->getRecipient();
        if ($recipient !== null) {
            $pool = new ViolationPool();
            $this->addressValidator->collect($recipient, $pool, AddressTypeEnum::Recipient);
            $violations->mergeUnder(Order::FIELD_RECIPIENT, $pool);
        }

        // The API reads the cover address as a second sender address and prints it, so it needs
        // what a sender needs. A block with anything in it is charged for, see
        // {@see \Polkurier\Webservice\Serializer\RequestQuirksNormalizer}.
        $coverAddress = $data->getCoverAddress();
        if ($coverAddress !== null) {
            $pool = new ViolationPool();
            $this->addressValidator->collect($coverAddress, $pool, AddressTypeEnum::Sender);
            $violations->mergeUnder(Order::FIELD_COVER_ADDRESS, $pool);
        }
    }
}
