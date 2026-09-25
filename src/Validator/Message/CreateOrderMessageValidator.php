<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Message;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Request\Order\CreateOrderMessage;
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
 * {@see \Polkurier\Webservice\Request\Order\CodMessage}, so they apply wherever that block is
 * sent.
 *
 * @extends AbstractMessageValidator<CreateOrderMessage>
 */
class CreateOrderMessageValidator extends AbstractMessageValidator
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
        /** @var CreateOrderMessage $data */
        $sender = new ViolationPool();
        $this->addressValidator->collect($data->getSender(), $sender, recipient: false);
        $violations->mergeUnder(CreateOrderMessage::FIELD_SENDER, $sender);

        $recipient = new ViolationPool();
        $this->addressValidator->collect($data->getRecipient(), $recipient, recipient: true);
        $violations->mergeUnder(CreateOrderMessage::FIELD_RECIPIENT, $recipient);
    }
}
