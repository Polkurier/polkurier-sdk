<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An order to price for one carrier.
 *
 * The order's fields are sent as they are for placing it, with `returnvaluations` next to them.
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::orderValuation()} builds it when
 * given a carrier. A rejected order field is reported under its own name, e.g. `sender.postcode`.
 *
 * @api
 */
final readonly class OrderValuationMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER = 'order';
    public const string FIELD_RETURN_VALUATIONS = 'returnvaluations';
    /**-@#*/

    // `order` names a field marked #[InlineFields], whose entries are written at this level, so
    // the key itself never reaches the payload. `returnvaluations` is the key the API reads the
    // carrier code from, which is why it sits on a property named for the carrier.

    /**
     * @param CreateOrderMessage $order the order to price. Its fields are sent at the top level, not
     *        under `order`
     * @param string $courier the carrier to price it for, a service code from
     *        {@see \Polkurier\Webservice\Api\Service\CourierServiceInterface::availableCarriers()}
     */
    public function __construct(
        /**
         * @var CreateOrderMessage
         */
        #[Serializer\SerializedName(self::FIELD_ORDER)]
        #[InlineFields]
        #[Validate\Valid]
        public CreateOrderMessage $order,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_VALUATIONS)]
        #[Validate\NotBlank]
        public string $courier,
    ) {}
}
