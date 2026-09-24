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
 * The order rules that cannot be declared on the fields themselves.
 *
 * Most of an order is checked by the attributes on its own messages: the carrier must be present,
 * the parcels positive, the postcodes in the right format. What is left here is the one rule that
 * depends on context rather than on any single value.
 *
 * That rule is the addresses. An email address is required of a sender and optional for a
 * recipient, and a recipient collecting from a pickup point needs no street address at all — so
 * which fields are required depends on the role the address is playing, which the address itself
 * does not know.
 *
 * Cash on delivery used to be the second, and is no longer: the rules for it belong to
 * {@see \Polkurier\Webservice\Request\Order\CodMessage} and are declared on it, so they
 * hold wherever a block of it is sent rather than only on an order.
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
